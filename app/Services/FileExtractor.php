<?php

namespace App\Services;

use Exception;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;

class FileExtractor
{
    public function extractText($filePath, $extension)
    {
        switch (strtolower($extension)) {
            case 'pdf':
                return $this->extractFromPdf($filePath);
            case 'doc':
            case 'docx':
                return $this->extractFromWord($filePath);
            case 'txt':
                return file_get_contents($filePath);
            default:
                throw new Exception("Unsupported file type: $extension");
        }
    }

    private function extractFromPdf($filePath)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }

    private function extractFromWord($filePath)
    {
        $phpWord = IOFactory::load($filePath);
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getTextRun')) { // cell/textrun
                     // This is a simplified extraction. For complex docx, recursive extraction is needed.
                     // But for this task, we'll keep it simple as per requirements usually implying basic text.
                }
            }
        }
        
        // Alternative simple extraction for docx if standard loop misses content:
        // Or better yet, just loop sections. The above might be brittle for nested elements.
        // Let's use a more robust recursive function approach or just try to extract basic paragraphs.
        
        // Re-implementing a simpler approach using transformation if possible, or just sticking to basic elements.
        // Many phpword examples suggest just loading and getting text is hard without saving to HTML/Text.
        // Let's safe convert to text writer.
        
        $objWriter = IOFactory::createWriter($phpWord, 'ODText'); // ODText is close to plain text? Or HTML?
        // Actually, just iterating elements is the standard way. Let's refine the loop below.
        
        $text = '';
        $sections = $phpWord->getSections();
        foreach ($sections as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    foreach ($element->getElements() as $textElement) {
                        if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                            $text .= $textElement->getText();
                        }
                    }
                } else if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                     $text .= $element->getText();
                } else if (method_exists($element, 'getText')) {
                    $text .= $element->getText();
                }
                $text .= "\n";
            }
        }
        
        return $text;
    }
}
