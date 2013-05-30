<?php

namespace Erichard\DmsBundle\Controller;

use Erichard\DmsBundle\Entity\Document;
use Erichard\DmsBundle\Form\DocumentType;
use Erichard\DmsBundle\Response\FileResponse;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function addAction($node)
    {
        $documentNode = $this->findNodeOrThrowError($node);
        $request      = $this->get('request');

        if ($request->isMethod('GET')) {
            return $this->render('ErichardDmsBundle:Document:add.html.twig', array(
                'node' => $documentNode
            ));
        } else {
            $filename = $request->request->get('filename');
            $documentNode->removeEmptyMetadatas();
            $document = new Document($documentNode);
            $document->setName($filename);
            $document->setOriginalName($filename);
            $document->setFilename($request->request->get('token'));

            $em = $this->get('doctrine')->getManager();
            $em->persist($document);
            $em->flush();

            $storageTmpPath = $this->container->getParameter('dms.storage.tmp_path');
            $storagePath    = $this->container->getParameter('dms.storage.path');

            $filesystem = $this->get('filesystem');

            $absTmpFilename = $storageTmpPath . '/' . $document->getFilename();
            $absFilename = $storagePath . '/' . $document->getComputedFilename();

            // move file
            if (!$filesystem->exists(dirname($absFilename))) {
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
                        'first' => true,
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
                $response->setData(array(
                    'jsonrpc' => '2.0',
                    'id'      => 'id',
                    'error'   => array(
                        'code'    => 103,
                        'message' => "Failed to move uploaded file.",
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

        return $this->render('ErichardDmsBundle:Document:show.html.twig', array(
            'node'     => $document->getNode(),
            'document' => $document,
        ));
    }

    public function editAction($node, $document)
    {
        //$documentNode = $this->findNodeOrThrowError($node);
        $document = $this->findDocumentOrThrowError($document, $node);

        $form = $this->createForm(new DocumentType(), $document);

        return $this->render('ErichardDmsBundle:Document:edit.html.twig', array(
            'node'     => $document->getNode(),
            'document' => $document,
            'form'     => $form->createView(),
        ));
    }

    public function updateAction($node, $document)
    {
        //$documentNode = $this->findNodeOrThrowError($node);
        $document = $this->findDocumentOrThrowError($document, $node);

        $form = $this->createForm(new DocumentType(), $document);
        $form->bind($this->get('request'));

        if (!$form->isValid()) {
            $response = $this->render('ErichardDmsBundle:Node:edit.html.twig', array(
                'node'     => $document->getNode(),
                'document' => $document,
                'form'     => $form->createView(),
            ));
        } else {
            $metadatas = $form->get('metadatas')->getData();
            foreach ($metadatas as $metaName => $metaValue) {
                $document->getMetadata($metaName)->setValue($metaValue);
            }

            $document->removeEmptyMetadatas();
            $em = $this->get('doctrine')->getManager();
            $em->persist($document);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Document successfully updated !');

            $response = $this->redirect($this->generateUrl('erichard_dms_show_document', array('node' => $document->getNode()->getSlug(), 'document' => $document->getSlug())));
        }

        return $response;
    }


    public function previewAction($dimension, $document, $node)
    {
        list($width, $height) = explode('x', $dimension);

        $request = $this->get('request');
        $imagine = new Imagine();
        $size    = new Box($width, $height);
        $mode    = ImageInterface::THUMBNAIL_INSET;

        $document = $this->findDocumentOrThrowError($document, $node);

        $absPath = $this->container->getParameter('dms.storage.path') . DIRECTORY_SEPARATOR . $document->getFilename();

        if (filesize($absPath) >= 5000000) {
            $picture = $this->get('kernel')->locateResource('@ErichardDmsBundle/Resources/public/img/picture.png');
            $image = $imagine->open($picture);
        } else {
            try {
                if (pathinfo($absPath, PATHINFO_EXTENSION) === 'pdf') {
                    $absPath .= '[0]';
                }
                $imagick = new \Imagick($absPath);
                $imagick->setCompression(\Imagick::COMPRESSION_LZW);
                $imagick->setResolution(72, 72);
                $imagick->setCompressionQuality(90);
                $image = new \Imagine\Imagick\Image($imagick);
            } catch (\ImagickException $e) {
                $picture = $this->get('kernel')->locateResource('@ErichardDmsBundle/Resources/public/img/picture.png');
                $image = $imagine->open($picture);
            }
        }

        $cacheFile = $this->get('kernel')->getRootDir() . '/../web' . $request->getRequestUri();

        if (!is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0777, true);
        }

        $image
            ->thumbnail($size, $mode)
            ->save($cacheFile, array('quality' => 90))
        ;

        $expireDate = new \DateTime();
        $expireDate->modify('+10 years');

        $response = new Response();

        $response->setPublic();
        $response->setExpires($expireDate);
        $response->setContent(file_get_contents($cacheFile));

        $finfo = new \finfo(FILEINFO_MIME);

        $response->headers->set('Content-Type', $finfo->file($cacheFile));
        $response->setPublic();
        $response->setSharedMaxAge('3600');

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

        $em = $this->get('doctrine')->getManager();
        $em->remove($document);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'Document successfully removed !');

        return $this->redirect($this->generateUrl('erichard_dms_node_list', array('node' => $document->getNode()->getSlug())));
    }

    public function removeAction($document, $node)
    {
        $document = $this->findDocumentOrThrowError($document, $node);

        return $this->render('ErichardDmsBundle:Document:remove.html.twig', array(
            'document' => $document,
            'node'     => $document->getNode()
        ));
    }


    public function findDocumentOrThrowError($document, $node)
    {
        return $this
            ->get('dms.repository.document')
            ->findDocumentOrThrowError($document, $node)
        ;
    }

    public function findNodeOrThrowError($node)
    {
        return $this
            ->get('dms.repository.documentNode')
            ->findNodeOrThrowError($node)
        ;
    }

}
