<?php

namespace Erichard\DmsBundle\Controller;

use Erichard\DmsBundle\Entity\Document;
use Erichard\DmsBundle\Entity\DocumentMetadata;
use Erichard\DmsBundle\Form\DocumentType;
use Erichard\DmsBundle\Response\FileResponse;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;

class DocumentController extends Controller
{
    public function addAction($node, $document = null)
    {
        $documentNode = $this->findNodeOrThrowError($node);
        $request      = $this->get('request');

        if (null !== $document) {
            $document = $this->findDocumentOrThrowError($document, $node);
            $firstEdition = false;
        } else {
            $document = new Document($documentNode);
            $firstEdition = true;
        }

        if ($request->isMethod('GET')) {
            $params = array(
                'node' => $documentNode,
            );

            if (null !== $document->getId()) {
                $params['document'] = $document;
            }

            return $this->render('ErichardDmsBundle:Document:add.html.twig', $params);
        } else {
            $filename = $request->request->get('filename');
            $documentNode->removeEmptyMetadatas();

            if (null === $document->getId()) {
                $document->setName($filename);
            }

            $document->setOriginalName($filename);
            $document->setFilename($request->request->get('token'));
            $document->removeEmptyMetadatas();

            foreach ($document->getNode()->getDocuments() as $sibling) {
                $sibling->removeEmptyMetadatas();
            }

            $em = $this->get('doctrine')->getManager();
            $em->persist($document);
            $em->flush();

            $storageTmpPath = $this->container->getParameter('dms.storage.tmp_path');
            $storagePath    = $this->container->getParameter('dms.storage.path');

            $filesystem = $this->get('filesystem');

            $absTmpFilename = $storageTmpPath . '/' . $document->getFilename();
            $absFilename = $storagePath . '/' . $document->getComputedFilename();

            // Delete existing thumbnails
            $finder = new Finder();
            $finder->files()
                ->in($this->container->getParameter('dms.storage.web_path').'/image')
                ->name("{$document->getSlug()}.png")
            ;
            foreach ($finder as $file) {
                $filesystem->remove($file);
            }

            // overwrite file
            if ($filesystem->exists($absFilename)) {
                $filesystem->remove($absFilename);
            } elseif (!$filesystem->exists(dirname($absFilename))) {
                $filesystem->mkdir(dirname($absFilename));
            }

            $filesystem->rename($absTmpFilename, $absFilename);
            $document->setFilename($document->getComputedFilename());

            $em->persist($document);
            $em->flush();

            return $this->redirect(
                $this->get('router')->generate(
                    'erichard_dms_edit_document',
                    array(
                        'document' => $document->getSlug(),
                        'node' => $documentNode->getSlug(),
                        'first' => $firstEdition,
                    )
                )
            );
        }
    }

    public function uploadAction()
    {
        $response = new JsonResponse();
        $response->expire();
        $response->setLastModified(new \DateTime());
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate', true);
        $response->headers->set('Cache-Control', 'post-check=0, pre-check=0', false);
        $response->headers->set('Pragma', 'no-cache');

        $request = $this->get('request');

        // Settings
        $targetDir = $this->container->getParameter('dms.storage.tmp_path');

        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds

        // 5 minutes execution time
        @set_time_limit(5 * 60);

        // Get parameters
        $chunk = $request->request->getInt('chunk', 0);
        $chunks = $request->request->getInt('chunks', 0);
        $origFileName = $request->request->get('name', '');

        if ('' === $origFileName) {
            $response->setData(array(
                'jsonrpc' => '2.0',
                'id'      => 'id',
                'error'   => array(
                    'code'    => 105,
                    'message' => "Failed to read filename from the request.",
                )
            ));

            return $response;
        }

        // Clean the fileName for security reasons
        $fileName = preg_replace('/[^\w\._]+/', '_', $origFileName);

        // Make sure the fileName is unique but only if chunking is disabled
        if ($chunks < 2 && is_file($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
            $fileinfo = pathinfo($fileName);

            $count = 1;
            while (is_file($targetDir . DIRECTORY_SEPARATOR . $fileinfo['filename'] . '_' . $count . '.' . $fileinfo['extension'])) {
                $count++;
            }

            $fileName = $fileinfo['filename'] . '_' . $count . '.' . $fileinfo['extension'];
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        // Create target dir
        if (!is_dir($targetDir)) {
            @mkdir($targetDir);
        }

        // Remove old temp files
        if ($cleanupTargetDir) {
            if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
                while (($file = readdir($dir)) !== false) {
                    $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                    // Remove temp file if it is older than the max age and is not the current file
                    if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
                        @unlink($tmpfilePath);
                    }
                }
                closedir($dir);
            } else {
                $response->setData(array(
                    'jsonrpc' => '2.0',
                    'id'      => 'id',
                    'error'   => array(
                        'code'    => 100,
                        'message' => "Failed to open temp directory.",
                    )
                ));

                return $response;
            }
        }

        // Look for the content type header
        $contentType = $request->headers->get('CONTENT_TYPE', $request->headers->get('HTTP_CONTENT_TYPE'));

        // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
        if (strpos($contentType, "multipart") !== false) {
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                // Open temp file
                $out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    // Read binary input stream and append it to temp file
                    $in = @fopen($_FILES['file']['tmp_name'], "rb");

                    if ($in) {
                        while ($buff = fread($in, 4096))
                            fwrite($out, $buff);
                    } else {
                        $response->setData(array(
                            'jsonrpc' => '2.0',
                            'id'      => 'id',
                            'error'   => array(
                                'code'    => 101,
                                'message' => "Failed to open input stream.",
                            )
                        ));

                        return $response;
                    }
                    @fclose($in);
                    @fclose($out);
                    @unlink($_FILES['file']['tmp_name']);
                } else {
                    $response->setData(array(
                        'jsonrpc' => '2.0',
                        'id'      => 'id',
                        'error'   => array(
                            'code'    => 102,
                            'message' => "Failed to open output stream.",
                        )
                    ));

                    return $response;
                }
            } else {

                $messages = array(
                    UPLOAD_ERR_INI_SIZE  => 'document.upload.error.filesize_bigger_than_allowed',
                    UPLOAD_ERR_FORM_SIZE => 'document.upload.error.filesize_bigger_than_allowed',
                    UPLOAD_ERR_PARTIAL => 'document.upload.error.file_partially_uploaded',
                    UPLOAD_ERR_NO_FILE => 'document.upload.error.no_file_uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'document.upload.error.no_temporary_folder',
                    UPLOAD_ERR_CANT_WRITE => 'document.upload.error.failed_to_write_to_disk',
                );

                $response->setData(array(
                    'jsonrpc' => '2.0',
                    'id'      => 'id',
                    'error'   => array(
                        'code'    => 103,
                        'message' => $this->get('translator')->trans($messages[$_FILES['file']['error']]),
                    )
                ));

                return $response;
            }
        } else {
            // Open temp file
            $out = @fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            if ($out) {
                // Read binary input stream and append it to temp file
                $in = @fopen("php://input", "rb");

                if ($in) {
                    while ($buff = fread($in, 4096))
                        fwrite($out, $buff);
                } else {
                    $response->setData(array(
                        'jsonrpc' => '2.0',
                        'id'      => 'id',
                        'error'   => array(
                            'code'    => 101,
                            'message' => "Failed to open input stream.",
                        )
                    ));

                    return $response;
                }

                @fclose($in);
                @fclose($out);
            } else {
                $response->setData(array(
                    'jsonrpc' => '2.0',
                    'id'      => 'id',
                    'error'   => array(
                        'code'    => 102,
                        'message' => "Failed to open output stream.",
                    )
                ));

                return $response;
            }
        }

        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$filePath}.part", $filePath);
        }

        $response->setData(array(
            'jsonrpc' => '2.0',
            'result'  => null,
            'id'      => 'id',
        ));

        return $response;
    }

    public function showAction($node, $document)
    {
        $document = $this->findDocumentOrThrowError($document, $node);
        $this->get('dms.manager')->getDocumentMetadatas($document);

        return $this->render('ErichardDmsBundle:Document:show.html.twig', array(
            'node'     => $document->getNode(),
            'document' => $document,
        ));
    }

    public function editAction($node, $document)
    {
        $document = $this->findDocumentOrThrowError($document, $node);
        $this->get('dms.manager')->getDocumentMetadatas($document);
        $form = $this->createForm(new DocumentType(), $document);

        return $this->render('ErichardDmsBundle:Document:edit.html.twig', array(
            'node'         => $document->getNode(),
            'document'     => $document,
            'form'         => $form->createView(),
            'firstEdition' => $this->get('request')->get('first', false)
        ));
    }

    public function updateAction($node, $document)
    {
        $document = $this->findDocumentOrThrowError($document, $node);
        $this->get('dms.manager')->getDocumentMetadatas($document);

        $form = $this->createForm(new DocumentType(), $document);
        $form->bind($this->get('request'));

        if (!$form->isValid()) {
            $response = $this->render('ErichardDmsBundle:Node:edit.html.twig', array(
                'node'     => $document->getNode(),
                'document' => $document,
                'form'     => $form->createView(),
            ));
        } else {
            $em = $this->get('doctrine')->getManager();

            $metadatas = $form->get('metadatas')->getData();
            foreach ($metadatas as $metaName => $metaValue) {

                if (null === $metaValue) {
                    if ($metadata = $document->getMetadata($metaName)) {
                        $document->removeMetadataByName($metaName);
                        $em->remove($metadata);
                    }
                    continue;
                }

                if (!$document->hasMetadata($metaName)) {
                    $metadata = new DocumentMetadata(
                        $em->getRepository('Erichard\DmsBundle\Entity\Metadata')->findOneByName($metaName)
                    );
                    $metadata->setValue($metaName);
                    $document->addMetadata($metadata);
                }

                $document->getMetadata($metaName)->setValue($metaValue);

                $em->persist($document->getMetadata($metaName));
            }

            $uploadedFile = $form->get('thumbnail')->getData();
            if (null !== $uploadedFile) {
                $dirname = dirname($document->getFilename());
                $absDirName = $this->container->getParameter('dms.storage.path') . DIRECTORY_SEPARATOR . $dirname;
                $filename = 'thumb_'.basename($document->getFilename());
                $uploadedFile->move($absDirName, $filename);
                $document->setThumbnail($dirname . DIRECTORY_SEPARATOR . $filename);
            }

            // Remove document's thumbnails
            $filesystem = $this->get('filesystem');
            $finder = new Finder();
            $finder->files()
                ->in($this->get('request')->server->get('DOCUMENT_ROOT'))
                ->name("{$document->getSlug()}.png");

            foreach ($finder as $file) {
                $filesystem->remove($file);
            }


            $em->persist($document);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'document.edit.successfully_updated');

            $response = $this->redirect($this->generateUrl('erichard_dms_node_list', array('node' => $document->getNode()->getSlug())));
        }

        return $response;
    }


    public function previewAction($dimension, $document, $node)
    {
        list($width, $height) = array_map('intval', explode('x', $dimension));

        $request = $this->get('request');
        $imagine = new Imagine();
        $size    = new Box($width, $height);
        $mode    = ImageInterface::THUMBNAIL_INSET;

        $document = $this->findDocumentOrThrowError($document, $node);
        $absPath  = $this->container->getParameter('dms.storage.path') . DIRECTORY_SEPARATOR . $document->getFilename();

        $thumbnailFile = $this->get('dms.manager')->generateThumbnail($document, $dimension);

        $expireDate = new \DateTime();
        $expireDate->modify('+10 years');

        $response = new FileResponse();
        $response->setFilename($thumbnailFile);
        $response->headers->set('Content-Type', 'image/png');

        $response->setPublic();
        $response->setExpires($expireDate);

        return $response;
    }

    public function downloadAction($document, $node)
    {
        $document = $this->findDocumentOrThrowError($document, $node);

        $absPath = $this->container->getParameter('dms.storage.path') . DIRECTORY_SEPARATOR . $document->getFilename();

        $response = new FileResponse();
        $response->setFilename($absPath);

        $response->headers->set('Cache-Control', 'public');
        $response->headers->set('Content-Type', $document->getMimeType());

        $userAgent = $this->get('request')->headers->get('User-Agent');

        if (preg_match('#MSIE|Safari|Konqueror#', $userAgent)) {
            $contentDisposition = "filename=".rawurlencode($document->getSlug().'.'.$document->getExtension());
        }

        $contentDisposition = "filename*=UTF-8''".rawurlencode($document->getSlug().'.'.$document->getExtension());
        $response->headers->set('Content-Disposition', 'attachment; '.$contentDisposition);

        return $response;
    }

    public function deleteAction($document, $node)
    {
        $document = $this->findDocumentOrThrowError($document, $node);

        $parentListUrl = $this->generateUrl('erichard_dms_node_list', array(
            'node' => $document->getNode()->getSlug()
        ));

        $backUrl = $this->get('request')->request->get('back', $parentListUrl);

        $em = $this->get('doctrine')->getManager();
        $em->remove($document);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'document.remove.successfully_removed');

        return $this->redirect($backUrl);
    }

    public function removeAction($document, $node)
    {
        $document = $this->findDocumentOrThrowError($document, $node);

        return $this->render('ErichardDmsBundle:Document:remove.html.twig', array(
            'document' => $document,
            'node'     => $document->getNode(),
            'backUrl'  => $this->get('request')->query->get('back')
        ));
    }

    public function linkAction($document, $node)
    {
        $request    = $this->get('request');
        $dmsManager = $this->get('dms.manager');
        $documentSlug = $document;
        $nodeSlug = $node;
        $document = $this->findDocumentOrThrowError($documentSlug, $nodeSlug);

        if ($request->isMethod('POST')) {
            $nodeId = $request->request->getInt('linkTo');
            $targetNode = $dmsManager->getNodeById($nodeId);
            if (!$this->get('security.context')->isGranted('DOCUMENT_ADD', $targetNode)) {
                throw new AccessDeniedHttpException("You are not allowed to access this document");
            }

            $link = clone $document;

            $em = $this->get('doctrine')->getManager();

            $document->addAlias($link);
            $link->setNode($targetNode);
            $link->removeEmptyMetadatas();
            $link->getNode()->removeEmptyMetadatas();
            foreach ($link->getNode()->getDocuments() as $document) {
                $document->removeEmptyMetadatas();
            }

            $em->persist($link);
            $em->flush();

            return $this->redirect($this->generateUrl('erichard_dms_link_document', array(
                'node' => $nodeSlug,
                'document' => $documentSlug
            )));
        }

        $targetNodeSlug= $request->query->get('target');

        if (null !== $targetNodeSlug) {
            $target = $dmsManager->getNode($targetNodeSlug);
            foreach ($target->getNodes() as $targetSubNode) {
                if (!$this->get('security.context')->isGranted('DOCUMENT_ADD', $targetSubNode)) {
                    $target->removeNode($targetSubNode);
                }
            }
        } else {
            $target = null;
        }

        return $this->render('ErichardDmsBundle:Document:link.html.twig', array(
            'document'      => $document,
            'node'          => $document->getNode(),
            'target'        => $target,
        ));
    }

    public function findDocumentOrThrowError($documentSlug, $nodeSlug)
    {
        try {
            $document = $this
                ->get('dms.manager')
                ->getDocument($documentSlug, $nodeSlug)
            ;
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }

        if (null === $document) {
            throw new NotFoundHttpException(sprintf('The document "%s" was not found', $documentSlug));
        }

        return $document;
    }

    public function findNodeOrThrowError($nodeSlug)
    {
        try {
            $node = $this
                ->get('dms.manager')
                ->getNode($nodeSlug)
            ;
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }

        if (null === $node) {
            throw new NotFoundHttpException(sprintf('The node "%s" was not found', $nodeSlug));
        }

        return $node;
    }

    public function removeThumbnailAction($document, $node)
    {
        $em = $this->get('doctrine')->getManager();
        $documentSlug = $document;
        $nodeSlug = $node;

        $document = $this->findDocumentOrThrowError($document, $node);
        $document->setThumbnail(null);
        $em->persist($document);
        $em->flush();

        $filesystem = $this->get('filesystem');

        // Remove document's thumbnails
        $finder = new Finder();
        $finder->files()
            ->in($this->get('request')->server->get('DOCUMENT_ROOT'))
            ->name("{$document->getSlug()}.png");

        foreach ($finder as $file) {
            $filesystem->remove($file);
        }

        return $this->redirect($this->generateUrl('erichard_dms_edit_document', array(
            'node' => $nodeSlug,
            'document' => $documentSlug
        )));
    }

}
