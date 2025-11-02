<?php

namespace App\Service;

use Smalot\PdfParser\Parser;
use Psr\Log\LoggerInterface;

/**
 * Service pour extraire le texte d'un fichier PDF
 */
class PdfExtractorService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Extrait le texte d'un fichier PDF
     *
     * @param string $filePath Chemin complet du fichier PDF
     * @return string Texte extrait du PDF
     * @throws \RuntimeException En cas d'erreur lors de la lecture
     */
    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Fichier PDF non trouvé: {$filePath}");
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);

            // Extraire le texte de toutes les pages
            $pages = $pdf->getPages();
            $fullText = '';

            foreach ($pages as $page) {
                $fullText .= $page->getText() . "\n";
            }

            // Nettoyer le texte
            $cleanedText = $this->cleanText($fullText);

            $this->logger->info('Texte PDF extrait avec succès', [
                'filePath' => $filePath,
                'textLength' => strlen($cleanedText)
            ]);

            return $cleanedText;

        } catch (\Exception $e) {
            $this->logger->error('Erreur extraction PDF', [
                'filePath' => $filePath,
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException("Impossible d'extraire le texte du PDF: " . $e->getMessage());
        }
    }

    /**
     * Nettoie le texte extrait
     *
     * @param string $text Texte brut
     * @return string Texte nettoyé
     */
    private function cleanText(string $text): string
    {
        // Supprimer les espaces excessifs
        $text = preg_replace('/\s+/', ' ', $text);

        // Supprimer les caractères de contrôle
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // Trim
        $text = trim($text);

        return $text;
    }
}
