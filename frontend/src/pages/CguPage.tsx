import React from 'react';
import { Link } from 'react-router-dom';

export const CguPage: React.FC = () => (
  <div className="min-h-screen bg-earth-50 px-4 py-12">
    <div className="mx-auto max-w-4xl rounded-2xl bg-white p-8 shadow-sm">
      <h1 className="text-3xl font-bold text-earth-900">Conditions d'utilisation</h1>
      <p className="mt-4 text-earth-600">
        TerraLink met en relation producteurs, acheteurs et livreurs. En utilisant la plateforme, vous acceptez les règles suivantes.
      </p>

      <h2 className="mt-8 text-xl font-semibold text-earth-900">1. Fraîcheur et retrait automatique</h2>
      <p className="mt-2 text-earth-600">
        Les lots sont automatiquement retirés du catalogue lorsqu'ils sont plus d'un jour au-delà de leur date d'expiration calculée à partir de la date de récolte et de la durée de conservation. Cette règle s'applique quotidiennement.
      </p>

      <h2 className="mt-6 text-xl font-semibold text-earth-900">2. Remise automatique</h2>
      <p className="mt-2 text-earth-600">
        Lorsqu'un lot expire dans les 24 heures, un prix réduit de 30% est appliqué automatiquement au moment du paiement. Le prix initial reste visible pour information, mais le montant réellement payé et les revenus crédités utilisent le prix réduit.
      </p>

      <h2 className="mt-6 text-xl font-semibold text-earth-900">3. Paiements et responsabilités</h2>
      <p className="mt-2 text-earth-600">
        Les paiements doivent être effectués via les moyens autorisés par la plateforme. Les utilisateurs doivent fournir des informations exactes et sont responsables de l'usage de leur compte. TerraLink agit comme intermédiaire technique et ne garantit pas la qualité physique des produits après la transaction.
      </p>

      <h2 className="mt-6 text-xl font-semibold text-earth-900">4. Livraison et litiges</h2>
      <p className="mt-2 text-earth-600">
        Les livraisons sont soumises aux règles de la plateforme et peuvent être contestées via le système de litige en cas de problème. Les utilisateurs doivent signaler tout incident rapidement pour permettre une résolution.
      </p>

      <div className="mt-8">
        <Link to="/" className="text-primary-600 font-semibold hover:text-primary-700">Retour à l'accueil</Link>
      </div>
    </div>
  </div>
);
