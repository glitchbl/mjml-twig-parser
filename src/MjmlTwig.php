<?php

namespace Glitchbl;

use Twig\Loader\ArrayLoader;
use Twig\Environment;
use Exception;

class MjmlTwig
{
    /**
     * @var string
     */
    protected string $mjml;

    /**
     * @param string $mjml
     * @return void
     */
    public function __construct(string $mjml)
    {
        $this->mjml = $mjml;
    }

    /**
     * @param string $url
     * @return self
     */
    static function createFromUrl(string $url): self
    {
        $mjml = self::curlGetContents($url);
        return new self($mjml);
    }

    /**
     * @param string $url
     * @return string
     */
    static protected function curlGetContents(string $url): string
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

    /**
     * @throws Exception
     * @return string
     */
    protected function createTemporaryFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'mjmltwig_');

        if (!$file)
            throw new Exception("Unable to create temporary file '{$file}'");

        return $file;
    }

    /**
     * @param array $variables
     * @throws Exception
     * @return string
     */
    public function parse(array $variables = []): string
    {
        $twig = new Environment(new ArrayLoader(['mjml' => $this->mjml]));
        $mjml = $twig->render('mjml', $variables);

        $tmp_file = $this->createTemporaryFile();
        file_put_contents($tmp_file, $mjml);

        $command = 'mjml '.escapeshellarg($tmp_file).' -s';
        $process = proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process))
            throw new Exception("Unable to execute {$command}");

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $status = proc_close($process);
        unlink($tmp_file);

        if ($status !== 0 && $error)
            throw new Exception($error);

        $output = trim(preg_replace('#<!--\s+[^>]+-->#i', '', $output));
        return $output;
    }
}
