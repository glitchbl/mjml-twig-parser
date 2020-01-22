<?php

namespace Glitchbl\MjmlTwig;

use Exception;

class Utils
{
    /**
     * @throws Exception
     * @return string
     */
    static public function createTemporaryFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'mjmltwig_');

        if (!$file)
            throw new Exception("Unable to create temporary file '{$file}'");

        return $file;
    }

    /**
     * @param string $url
     * @return string
     */
    static public function curlGetContents(string $url): string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        $content = curl_exec($curl);
        curl_close($curl);
        return $content;
    }
}
