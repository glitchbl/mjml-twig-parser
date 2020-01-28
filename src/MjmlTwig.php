<?php

namespace Glitchbl;

use Twig\Loader\ArrayLoader;
use Twig\Environment;
use Exception;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

class MjmlTwig
{
    /**
     * @var string
     */
    protected string $mjml_twig;

    /**
     * @var Environment
     */
    protected Environment $twig;

    /**
     * @var FilesystemLoader
     */
    protected FilesystemLoader $loader;

    /**
     * @param string $mjml_twig
     * @return void
     */
    public function __construct(string $mjml_twig)
    {
        $this->mjml_twig = $mjml_twig;
        $this->loader = new FilesystemLoader;
        $this->twig = new Environment(new ChainLoader([new ArrayLoader(['mjml_twig' => $this->mjml_twig]), $this->loader]));
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
     * @return Environment
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * @return FilesystemLoader
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * @param array $variables
     * @throws Exception
     * @return string
     */
    public function parse(array $variables = []): string
    {
        $mjml = $this->twig->render('mjml_twig', $variables);

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
