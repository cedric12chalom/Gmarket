<?php

namespace App\Enum;

enum BoutiqueTheme: string
{
    // Thèmes prédéfinis proposés à la création de la boutique.
    // Chaque thème porte un rendu visuel distinct (ci-dessous : description
    // utilisée par l'API pour adapter la page de boutique).
    case CLASSIQUE = 'classique';
    case SNEAKERS = 'sneakers';
    case LINGERIE = 'lingerie';
    case STREETWEAR = 'streetwear';
    case ELEGANCE = 'elegance';
    case MINIMALISTE = 'minimaliste';
    case VINTAGE = 'vintage';
    case PREMIUM = 'premium';

    /**
     * Description du rendu visuel pour chaque thème (exposée par l'API).
     */
    public function rendu(): string
    {
        return match ($this) {
            self::CLASSIQUE => 'Ambiance sobre et intemporelle, tons neutres, typographie classique.',
            self::SNEAKERS => 'Style urbain et dynamique, couleurs vives, accents street.',
            self::LINGERIE => 'Rendu doux et élégant, tons pastel, mise en valeur des matières.',
            self::STREETWEAR => 'Esprit street, forts contrastes, typographie impact.',
            self::ELEGANCE => 'Minimal chic, tons profonds, packaging soigné.',
            self::MINIMALISTE => 'Beaucoup d’espace blanc, lignes simples, focus produit.',
            self::VINTAGE => 'Ambiance rétro, tons chauds, textures anciennes.',
            self::PREMIUM => 'Rendu luxe, doré et noir, typographie raffinée.',
        };
    }

    /**
     * Couleur d'accent CSS exposée à l'API.
     */
    public function accent(): string
    {
        return match ($this) {
            self::CLASSIQUE => '#334155',
            self::SNEAKERS => '#facc15',
            self::LINGERIE => '#f9a8d4',
            self::STREETWEAR => '#0f172a',
            self::ELEGANCE => '#1e293b',
            self::MINIMALISTE => '#94a3b8',
            self::VINTAGE => '#b45309',
            self::PREMIUM => '#d4af37',
        };
    }
}