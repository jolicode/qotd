<?php

namespace App\Slack\BlockKit\Block\Image;

trait ImageRendererTrait
{
    public function render(array $block): string
    {
        $output = '';
        if (\array_key_exists('title', $block)) {
            $output .= '<small>';
            $output .= $this->plainTextRenderer->render(['text' => $block['title']]);
            $output .= '</small> ';
        }

        $output .= \sprintf('<img src="%s" alt="%s">', $block['image_url'] ?? $block['slack_file']['url'], $block['alt_text']);

        return "{$output} <br />\n";
    }
}
