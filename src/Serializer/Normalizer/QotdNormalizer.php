<?php

namespace App\Serializer\Normalizer;

use App\Entity\Qotd;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment;

class QotdNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
        private readonly Environment $twig,
    ) {
    }

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        $data['html'] = $this->twig->render('qotd/_message.html.twig', [
            'qotd' => $object,
        ]);

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Qotd;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Qotd::class => true];
    }
}
