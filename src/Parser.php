<?php

namespace Glitchbl\MjmlTwig;

use Twig\Loader\ArrayLoader;
use Twig\Environment;
use Exception;

class Parser
{
    /**
     * @var string
     */
    protected string $mjml;

    /**
     * @param string $mjml
     * @return void
     */
    private function __construct(string $mjml)
    {
        $this->mjml = $mjml;
    }

    /**
     * @param string $url
     * @return self
     */
    static function createFromUrl(string $url): self
    {
        $mjml = Utils::curlGetContents($url);
        return self::createFromString($mjml);
    }

    /**
     * @param string $mjml
     * @return self
     */
    static function createFromString(string $mjml): self
    {
        return new static($mjml);
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

        $tmp_file = Utils::createTemporaryFile();
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

        if (proc_close($process) !== 0 && $error)
            throw new Exception($error);

        return $output;
    }
}
