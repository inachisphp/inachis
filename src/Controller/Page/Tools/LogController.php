<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for displaying application logs
 */
#[IsGranted('ROLE_ADMIN')]
class LogController extends AbstractInachisController
{
    /**
     * Show the logs page
     * 
     * @param Request $request The request
     * @return Response The response
     */
    #[Route('/incc/tools/logs', name: 'incc_tools_logs')]
    public function showLogs(Request $request): Response
    {
        $logPath = $this->getParameter('kernel.project_dir') . '/var/log/dev.log';
        if (!file_exists($logPath) || !is_readable($logPath)) {
            throw $this->createNotFoundException('Log file not readable or does not exist');
        }

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        $filter = $request->request->all('filter');

        $allLines = $this->getLastLines($logPath, 1000);
        $parsedLines = [];
        foreach ($allLines as $line) {
            if (isset($filter['keyword'])) {
                if (strpos($line, $filter['keyword']) === false) {
                    continue;
                }
            }
            $parsed = $this->parseMonologLine($line);
            if (!$parsed) {
                continue;
            }
            if (in_array($parsed['level'], ['WARNING', 'ERROR', 'CRITICAL'])) {
                $parsedLines[] = $parsed;
            }
        }

        $this->data['form'] = $form->createView();
        $this->data['entries'] = $parsedLines;
        $this->data['filter'] = $filter;
        $this->data['page']['title'] = 'Logs';
        $this->data['page']['tab'] = 'logs';
        return $this->render('inadmin/page/tools/log.html.twig', $this->data);
    }

    /**
     * Get the last N lines from a file
     * @todo move this into a service
     * 
     * @param string $file The file to read
     * @param int $maxLines The maximum number of lines to read
     * @return array The last N lines from the file
     */
    private function getLastLines(string $file, int $maxLines): array
    {
        $handle = fopen($file, 'rb');
        if (!$handle) return [];

        $buffer = '';
        $pos = -2;
        $lineCount = 0;

        fseek($handle, 0, SEEK_END);

        while ($lineCount < $maxLines && fseek($handle, $pos, SEEK_END) !== -1) {
            $char = fgetc($handle);
            if ($char === "\n") $lineCount++;
            $buffer = $char . $buffer;
            $pos--;
        }

        fclose($handle);

        return array_filter(explode("\n", trim($buffer)));
    }

    /**
     * Parse a monolog log line
     * @todo move this into a service
     * 
     * @param string $line The log line to parse
     * @return array|null The parsed log line
     */
    private function parseMonologLine(string $line): ?array
    {
        if (!preg_match('/^\[(.*?)\]\s+([^.]+)\.([A-Z]+):\s+(.*)$/', $line, $matches)) {
            return null;
        }

        return [
            'timestamp' => $matches[1],
            'channel' => $matches[2],
            'level' => $matches[3],
            'message' => $matches[4],
            'raw' => $line,
        ];
    }
}