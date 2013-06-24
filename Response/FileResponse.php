<?php

namespace Erichard\DmsBundle\Response;

use Symfony\Component\HttpFoundation\Response;

class FileResponse extends Response
{
    private $filename;

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function sendContent()
    {
        if (empty($this->filename)) {
            return '';
        }

        $file = fopen($this->filename, 'rb');
        $out = fopen('php://output', 'wb');

        stream_copy_to_stream($file, $out);

        fclose($out);
        fclose($file);
    }
}
