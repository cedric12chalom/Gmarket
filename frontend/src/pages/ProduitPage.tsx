import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { ShoppingCart, ChevronLeft, Check, Paintbrush } from 'lucide-react'
import TShirtColorChanger from '../components/TShirtColorChanger'
import api from '../services/api'

export default function ProduitPage() {
  const { id } = useParams()
  const [produit, setProduit] = useState<any>(null)
  const [selectedImg, setSelectedImg] = useState(0)
  const [selectedColor, setSelectedColor] = useState('')
  const [selectedTaille, setSelectedTaille] = useState('')
  const [ajouté, setAjouté] = useState(false)
  const [useColorChanger, setUseColorChanger] = useState(false)

  useEffect(() => {
    if (!id) return
    api.get(`/api/produits/${id}`).then((r) => {
      const p = r.data.produit
      setProduit(p)
      if (p.couleurs?.length) setSelectedColor(p.couleurs[0])
    })
  }, [id])

  if (!produit) return <div className="text-center py-20 text-gray-400">Chargement...</div>

  const couleurs = produit.couleurs ?? []

  const variantesDisponibles = (produit.variantes ?? []).filter((v: any) =>
    (!selectedColor || v.couleur === selectedColor) && v.stock > 0
  )
  const taillesDispo = [...new Set(variantesDisponibles.map((v: any) => v.taille))] as string[]
  const stockRestant = variantesDisponibles.find((v: any) => v.taille === selectedTaille)?.stock ?? 0

  const handleAjouter = () => {
    setAjouté(true)
    setTimeout(() => setAjouté(false), 2000)
  }

  return (
    <div>
      <Link to="/catalogue" className="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary mb-6 transition">
        <ChevronLeft className="w-4 h-4" /> Retour au catalogue
      </Link>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        {/* Images */}
        <div>
          {useColorChanger ? (
            <TShirtColorChanger imageUrl={produit.images?.[selectedImg] ?? produit.imagePrincipale} alt={produit.nom} className="aspect-square mb-3" />
          ) : (
            <div className="aspect-square bg-gray-100 rounded-2xl overflow-hidden mb-3">
              <img src={produit.images?.[selectedImg] ?? produit.imagePrincipale} alt={produit.nom} className="w-full h-full object-cover" />
            </div>
          )}

          {/* Toggle color changer — visible seulement pour les t-shirts */}
          <div className="flex items-center gap-2 mb-3">
            <button onClick={() => setUseColorChanger(!useColorChanger)}
              className={`flex items-center gap-2 px-4 py-2 rounded-full text-sm border-2 transition ${useColorChanger ? 'border-accent bg-accent/10 font-semibold' : 'border-gray-200 hover:border-gray-300'}`}>
              <Paintbrush className="w-4 h-4" />
              {useColorChanger ? 'Voir la photo originale' : 'Essayer une autre couleur'}
            </button>
          </div>

          {produit.images?.length > 1 && (
            <div className="flex gap-2">
              {produit.images.map((img: string, i: number) => (
                <button key={i} onClick={() => setSelectedImg(i)} className={`w-16 h-16 rounded-lg overflow-hidden border-2 ${i === selectedImg ? 'border-accent' : 'border-transparent'}`}>
                  <img src={img} className="w-full h-full object-cover" />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Détails */}
        <div>
          <Link to={`/boutique/${produit.boutique?.slug}`} className="text-sm text-accent font-semibold hover:underline">{produit.boutique?.nom}</Link>
          <h1 className="text-2xl font-bold text-primary mt-2">{produit.nom}</h1>

          <div className="flex items-center gap-3 mt-4">
            <span className="text-3xl font-bold text-primary">{produit.prixActuel?.toLocaleString()} FCFA</span>
            {produit.pourcentageReduction > 0 && (
              <>
                <span className="text-lg text-gray-400 line-through">{produit.prix?.toLocaleString()} FCFA</span>
                <span className="bg-red-100 text-red-600 text-sm px-2 py-0.5 rounded-full font-semibold">-{produit.pourcentageReduction}%</span>
              </>
            )}
          </div>

          {produit.description && <p className="text-gray-600 mt-4">{produit.description}</p>}

          {/* Couleurs */}
          {couleurs.length > 0 && (
            <div className="mt-6">
              <p className="text-sm font-semibold text-gray-700 mb-2">Couleur : <span className="text-primary">{selectedColor}</span></p>
              <div className="flex gap-2">
                {couleurs.map((c: string) => (
                  <button key={c} onClick={() => { setSelectedColor(c); setSelectedTaille('') }}
                    className={`px-4 py-2 rounded-xl text-sm border-2 transition ${c === selectedColor ? 'border-accent bg-accent/10 font-semibold' : 'border-gray-200 hover:border-gray-300'}`}>
                    {c}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Tailles */}
          {taillesDispo.length > 0 && (
            <div className="mt-4">
              <p className="text-sm font-semibold text-gray-700 mb-2">Taille :</p>
              <div className="flex gap-2">
                {taillesDispo.map((t: string) => (
                  <button key={t} onClick={() => setSelectedTaille(t)}
                    className={`w-12 h-12 rounded-xl text-sm border-2 font-medium transition ${t === selectedTaille ? 'border-accent bg-accent/10' : 'border-gray-200 hover:border-gray-300'}`}>
                    {t}
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Stock */}
          {selectedTaille && (
            <p className="text-sm text-gray-500 mt-3">
              {stockRestant > 0 ? `${stockRestant} en stock` : <span className="text-red-500 font-semibold">Rupture de stock</span>}
            </p>
          )}

          {/* Bouton ajouter */}
          <button onClick={handleAjouter} disabled={!selectedTaille || stockRestant <= 0 || ajouté}
            className="mt-6 w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-slate-700 transition disabled:opacity-50 flex items-center justify-center gap-2">
            {ajouté ? <><Check className="w-5 h-5" /> Ajouté !</> : <><ShoppingCart className="w-5 h-5" /> Ajouter au panier</>}
          </button>

          {/* Couleurs dispo résumé */}
          <div className="mt-4 flex flex-wrap gap-2">
            {couleurs.map((c: string) => (
              <span key={c} className="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">{c}</span>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}
