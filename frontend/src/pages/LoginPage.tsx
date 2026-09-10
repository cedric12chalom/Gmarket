import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Mail, Lock } from 'lucide-react'
import { useAuthStore } from '../stores/authStore'

export default function LoginPage() {
  const { login } = useAuthStore()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const submit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await login(email, password)
      navigate('/')
    } catch {
      setError('Email ou mot de passe incorrect.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="max-w-md mx-auto mt-8">
      <h1 className="text-3xl font-bold text-primary mb-8 text-center">Connexion</h1>
      <form onSubmit={submit} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 space-y-5">
        {error && <div className="bg-red-50 text-red-600 p-3 rounded-xl text-sm">{error}</div>}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <div className="flex items-center border rounded-xl px-3">
            <Mail className="w-4 h-4 text-gray-400" />
            <input type="email" required value={email} onChange={(e) => setEmail(e.target.value)}
              className="w-full px-3 py-3 outline-none text-sm" placeholder="votre@email.com" />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
          <div className="flex items-center border rounded-xl px-3">
            <Lock className="w-4 h-4 text-gray-400" />
            <input type="password" required value={password} onChange={(e) => setPassword(e.target.value)}
              className="w-full px-3 py-3 outline-none text-sm" placeholder="••••••••" />
          </div>
        </div>
        <button disabled={loading} type="submit" className="w-full bg-primary text-white py-3 rounded-xl font-semibold hover:bg-slate-700 transition disabled:opacity-60">
          {loading ? 'Connexion...' : 'Se connecter'}
        </button>
        <p className="text-center text-sm text-gray-500">
          Pas encore de compte ? <Link to="/inscription" className="text-accent font-semibold hover:underline">S'inscrire</Link>
        </p>
      </form>
    </div>
  )
}
