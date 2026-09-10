import { Link } from 'react-router-dom'
import { Store, TrendingUp, Sparkles, ArrowRight } from 'lucide-react'
import { useEffect, useState } from 'react'
import api from '../services/api'

export default function HomePage() {
  const [produits, setProduits] = useState<any[]>([])

  useEffect(() => {
    api.get('/api/produits').then((r) => setProduits(r.data.produits.slice(0, 8)))
  }, [])

  return (
    <div className="space-y-16">
      {/* Hero */}
      <section className="text-center py-16">
        <h1 className="text-5xl font-extrabold text-primary mb-4">
          La mode <span className="text-accent">C2C</span> au Cameroun
        </h1>
        <p className="text-lg text-gray-600 max-w-2xl mx-auto mb-8">
          Achetez et vendez des vêtements, chaussures et accessoires directement entre particuliers.
          Créez votre boutique en quelques clics.
        </p>
        <div className="flex justify-center gap-4">
          <Link to="/catalogue" className="bg-primary text-white px-8 py-3 rounded-full font-semibold hover:bg-slate-700 transition flex items-center gap-2">
            Explorer le catalogue <ArrowRight className="w-4 h-4" />
          </Link>
          <Link to="/inscription" className="border-2 border-primary text-primary px-8 py-3 rounded-full font-semibold hover:bg-primary hover:text-white transition">
            Ouvrir ma boutique
          </Link>
        </div>
      </section>

      {/* Stats */}
      <section className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {[
          { icon: Store, label: 'Boutiques', value: '4+' },
          { icon: TrendingUp, label: 'Produits en ligne', value: '8+' },
          { icon: Sparkles, label: 'Catégories mode', value: '5' },
        ].map(({ icon: Icon, label, value }) => (
          <div key={label} className="text-center p-8 bg-white rounded-2xl shadow-sm border border-gray-100">
            <Icon className="w-8 h-8 text-accent mx-auto mb-3" />
            <p className="text-3xl font-bold text-primary">{value}</p>
            <p className="text-gray-500 mt-1">{label}</p>
          </div>
        ))}
      </section>

      {/* Derniers produits */}
      <section>
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-primary">Dernières nouveautés</h2>
          <Link to="/catalogue" className="text-accent hover:underline text-sm font-semibold">Voir tout →</Link>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {produits.map((p) => (
            <Link key={p.id} to={`/produit/${p.id}`} className="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-md transition group">
              <div className="aspect-square bg-gray-100 overflow-hidden">
                <img src={p.images?.[0] ?? p.imagePrincipale} alt={p.nom} className="w-full h-full object-cover group-hover:scale-105 transition" />
              </div>
              <div className="p-3">
                <p className="text-xs text-gray-400 mb-1">{p.boutique?.nom}</p>
                <h3 className="font-semibold text-sm text-primary line-clamp-1">{p.nom}</h3>
                <div className="flex items-center gap-2 mt-2">
                  <span className="font-bold text-primary">{p.prixActuel?.toLocaleString()} FCFA</span>
                  {p.pourcentageReduction > 0 && (
                    <span className="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">-{p.pourcentageReduction}%</span>
                  )}
                </div>
              </div>
            </Link>
          ))}
        </div>
      </section>

      {/* CTA */}
      <section className="bg-primary rounded-3xl p-12 text-center text-white">
        <h2 className="text-3xl font-bold mb-4">Vous êtes créateur de mode ?</h2>
        <p className="text-white/80 max-w-xl mx-auto mb-6">
          Ouvrez votre boutique sur Gmarket et touchez des milliers d'acheteurs au Cameroun. 30 jours d'essai gratuits.
        </p>
        <Link to="/inscription" className="bg-accent text-primary px-8 py-3 rounded-full font-bold hover:bg-yellow-400 transition inline-block">
          Commencer maintenant
        </Link>
      </section>
    </div>
  )
}
