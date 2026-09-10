import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Save } from 'lucide-react'
import { useAuthStore } from '../stores/authStore'
import api from '../services/api'

export default function MonComptePage() {
  const { user, fetchMe } = useAuthStore()
  const navigate = useNavigate()
  const [form, setForm] = useState({ prenom: '', nom: '', tiktok: '', instagram: '', snapchat: '', facebook: '', telephone: '' })
  const [saved, setSaved] = useState(false)

  useEffect(() => {
    if (!user) return
    setForm({
      prenom: user.prenom ?? '',
      nom: user.nom ?? '',
      tiktok: user.tiktok ?? '',
      instagram: user.instagram ?? '',
      snapchat: (user as any).snapchat ?? '',
      facebook: (user as any).facebook ?? '',
      telephone: (user as any).telephone ?? '',
    })
  }, [user])

  if (!user) { navigate('/connexion'); return null }

  const update = (k: string, v: string) => setForm((f) => ({ ...f, [k]: v }))

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    await api.put('/api/profil', form)
    await fetchMe()
    setSaved(true)
    setTimeout(() => setSaved(false), 2000)
  }

  const isVendeur = user.roles.includes('ROLE_VENDEUR')

  return (
    <div className="max-w-2xl mx-auto">
      <h1 className="text-3xl font-bold text-primary mb-8">Mon compte</h1>

      {/* Info utilisateur */}
      <div className="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
        <div className="flex items-center gap-4 mb-6">
          <div className="w-16 h-16 rounded-full bg-accent/10 flex items-center justify-center text-xl font-bold text-primary">
            {user.photo ? <img src={user.photo} className="w-full h-full rounded-full object-cover" /> : (user.prenom?.[0] ?? user.email[0]).toUpperCase()}
          </div>
          <div>
            <p className="font-bold text-primary">{user.prenom} {user.nom}</p>
            <p className="text-sm text-gray-500">{user.email}</p>
            <div className="flex gap-2 mt-1">
              {user.roles.map((r) => (
                <span key={r} className="text-xs bg-accent/10 text-primary px-2 py-0.5 rounded-full font-medium">
                  {r === 'ROLE_VENDEUR' ? 'Vendeur' : r === 'ROLE_ACHETEUR' ? 'Acheteur' : r}
                </span>
              ))}
            </div>
          </div>
        </div>

        {/* Abonnement */}
        {user.abonnement && (
          <div className="bg-gray-50 rounded-xl p-4 mb-6">
            <p className="text-sm font-semibold text-gray-700">Abonnement</p>
            <p className="text-sm text-gray-500">
              Statut : <span className="font-medium text-primary">{user.abonnement.statut}</span>
              {user.abonnement.dateFin && ` — expire le ${new Date(user.abonnement.dateFin).toLocaleDateString('fr-FR')}`}
            </p>
          </div>
        )}

        {/* Boutiques */}
        {isVendeur && user.boutiques && user.boutiques.length > 0 && (
          <div className="bg-gray-50 rounded-xl p-4 mb-6">
            <p className="text-sm font-semibold text-gray-700 mb-2">Mes boutiques</p>
            {user.boutiques.map((b) => (
              <div key={b.id} className="flex items-center gap-3 text-sm">
                <div className="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold" style={{ backgroundColor: b.themeAccent + '20', color: b.themeAccent }}>
                  {b.nom[0]}
                </div>
                <span className="font-medium">{b.nom}</span>
                <a href={`/boutique/${b.slug}`} className="text-accent hover:underline text-xs">Voir →</a>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Formulaire profil */}
      <form onSubmit={submit} className="bg-white rounded-2xl border border-gray-100 p-6 space-y-4">
        <h2 className="font-bold text-primary">Modifier mon profil</h2>
        {saved && <div className="bg-green-50 text-green-600 p-3 rounded-xl text-sm">Profil sauvegardé !</div>}

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Prénom</label>
            <input value={form.prenom} onChange={(e) => update('prenom', e.target.value)} className="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-accent" />
          </div>
          <div>
            <label className="block text-xs font-medium text-gray-600 mb-1">Nom</label>
            <input value={form.nom} onChange={(e) => update('nom', e.target.value)} className="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-accent" />
          </div>
        </div>
        <div>
          <label className="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
          <input value={form.telephone} onChange={(e) => update('telephone', e.target.value)} className="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-accent" placeholder="+237 6XX XXX XXX" />
        </div>

        <div className="border-t border-gray-100 pt-4">
          <p className="text-xs font-semibold text-gray-500 uppercase mb-3">Réseaux sociaux</p>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">TikTok</label>
              <input value={form.tiktok} onChange={(e) => update('tiktok', e.target.value)} className="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-accent" placeholder="@pseudo" />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Instagram</label>
              <input value={form.instagram} onChange={(e) => update('instagram', e.target.value)} className="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-accent" placeholder="@pseudo" />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Snapchat</label>
              <input value={form.snapchat} onChange={(e) => update('snapchat', e.target.value)} className="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-accent" placeholder="@pseudo" />
            </div>
            <div>
              <label className="block text-xs font-medium text-gray-600 mb-1">Facebook</label>
              <input value={form.facebook} onChange={(e) => update('facebook', e.target.value)} className="w-full border rounded-xl px-3 py-2.5 text-sm outline-none focus:border-accent" placeholder="URL ou pseudo" />
            </div>
          </div>
        </div>

        <button type="submit" className="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-slate-700 transition flex items-center justify-center gap-2">
          <Save className="w-4 h-4" /> Enregistrer
        </button>
      </form>
    </div>
  )
}
