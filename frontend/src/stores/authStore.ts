import { create } from 'zustand'
import api from '../services/api'

interface Boutique {
  id: number
  nom: string
  slug: string
  theme: string
  themeAccent: string
  tiktokPseudo?: string | null
  description?: string | null
  accentColor?: string
  customColors?: Record<string, string> | null
  palette?: Record<string, string>
}

interface User {
  id: number
  email: string
  prenom: string | null
  nom: string | null
  photo: string | null
  tiktok: string | null
  instagram: string | null
  roles: string[]
  boutiques?: Boutique[]
  abonnement?: { statut: string; dateFin: string } | null
}

interface AuthState {
  token: string | null
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (data: { email: string; password: string; prenom: string; nom: string; role?: string }) => Promise<void>
  logout: () => void
  fetchMe: () => Promise<void>
}

export const useAuthStore = create<AuthState>((set, get) => ({
  token: localStorage.getItem('gm_token'),
  user: null,
  loading: true,

  login: async (email, password) => {
    const { data } = await api.post('/api/auth/login', { email, password })
    localStorage.setItem('gm_token', data.token)
    set({ token: data.token })
    await get().fetchMe()
  },

  register: async (payload) => {
    const { data } = await api.post('/api/auth/register', payload)
    if (data.token) {
      localStorage.setItem('gm_token', data.token)
      set({ token: data.token })
      await get().fetchMe()
    }
  },

  logout: () => {
    localStorage.removeItem('gm_token')
    set({ token: null, user: null })
  },

  fetchMe: async () => {
    const token = get().token
    if (!token) { set({ loading: false }); return }
    try {
      const { data } = await api.get('/api/auth/me')
      set({ user: data.user, loading: false })
    } catch {
      localStorage.removeItem('gm_token')
      set({ token: null, user: null, loading: false })
    }
  },
}))
