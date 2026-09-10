import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Mail, Lock, User } from 'lucide-react'
import { useAuthStore } from '../stores/authStore'

export default function RegisterPage() {
  const { register } = useAuthStore()
  const navigate = useNavigate()
  const [form, setForm] = useState({ email: '', password: '', prenom: '', nom: '', role: 'acheteur' })
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const update = (k: string, v: string) => setForm((f) => ({ ...f, [k]: v }))

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await register(form)
      navigate(form.role === 'vendeur' ? '/ma-boutique' : '/catalogue')
    } catch {
      setError("Erreur lors de l'inscription.")
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="max-w-md mx-auto mt-8">
      <h1 className="text-3xl font-bold text-primary mb-8 text-center">Inscription</h1>
      <form onSubmit={submit} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-5">
        {error && <div className="bg-red-50 text-red-600 p-3 rounded-xl text-sm">{error}</div>}

        <div className="flex gap-4">
          {(['acheteur', 'vendeur'] as const).map((r) => (
            <button key={r} type="button" onClick={() => update('role', r)}
              className={`flex-1 py-3 rounded-xl text-sm font-semibold border-2 transition ${form.role === r ? 'border-accent bg-accent/10 text-primary' : 'border-gray-200 text-gray-500'}`}>
              {r === 'acheteur' ? 'Acheteur' : 'Vendeur / Créateur'}
            </button>
          ))}
        </div>

        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
            <div className="flex items-center border rounded-xl px-3">
              <User className="w-4 h-4 text-gray-400" />
              <input required value={form.prenom} onChange={(e) => update('prenom', e.target.value)}
                className="w-full px-3 py-3 outline-none text-sm" placeholder="Prénom" />
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Nom</label>
            <div className="flex items-center border rounded-xl px-3">
              <input required value={form.nom} onChange={(e) => update('nom', e.target.value)}
                className="w-full px-3 py-3 outline-none text-sm" placeholder="Nom" />
            </div>
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <div className="flex items-center border rounded-xl px-3">
            <Mail className="w-4 h-4 text-gray-400" />
            <input type="email" required value={form.email} onChange={(e) => update('email', e.target.value)}
              className="w-full px-3 py-3 outline-none text-sm" placeholder="votre@email.com" />
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
          <div className="flex items-center border rounded-xl px-3">
            <Lock className="w-4 h-4 text-gray-400" />
            <input type="password" required minLength={8} value={form.password} onChange={(e) => update('password', e.target.value)}
              className="w-full px-3 py-3 outline-none text-sm" placeholder="8 caractères min." />
          </div>
        </div>

        <button disabled={loading} type="submit" className="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-slate-700 transition disabled:opacity-60">
          {loading ? 'Inscription...' : "S'inscrire"}
        </button>
        <p className="text-center text-sm text-gray-500">
          Déjà un compte ? <Link to="/connexion" className="text-accent font-semibold hover:underline">Se connecter</Link>
        </p>
      </form>
    </div>
  )
}
