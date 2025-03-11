<?php

namespace App\Slack\BlockKit\Block\Section;

use App\Slack\BlockKit\Block\BlockRendererInterface;

class FieldsRenderer implements BlockRendererInterface
{
    public function __construct(
        private PlainTextRenderer $plainTextRenderer,
    ) {
    }

    public function render(array $block): string
    {
        $output = '';
        foreach ($block['fields'] as $k => $field) {
            if (0 !== $k) {
                $output .= "<br />\n";
            }
            $output .= $this->plainTextRenderer->renderText($field['text']);
        }

        return $output;
    }
}
