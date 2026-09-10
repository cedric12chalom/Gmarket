import { Link, Outlet, useNavigate } from 'react-router-dom'
import { ShoppingBag, LogOut, Menu, X } from 'lucide-react'
import { useState } from 'react'
import { useAuthStore } from '../stores/authStore'

export default function Layout() {
  const { user, logout } = useAuthStore()
  const navigate = useNavigate()
  const [menuOpen, setMenuOpen] = useState(false)

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-primary text-white sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
          <Link to="/" className="flex items-center gap-2 text-xl font-bold">
            <ShoppingBag className="w-6 h-6 text-accent" />
            Gmarket
          </Link>

          <nav className="hidden md:flex items-center gap-6 text-sm">
            <Link to="/catalogue" className="hover:text-accent transition">Catalogue</Link>
            {user && (
              <>
                <Link to="/ma-boutique" className="hover:text-accent transition">Ma boutique</Link>
                <Link to="/commandes" className="hover:text-accent transition">Commandes</Link>
              </>
            )}
          </nav>

          <div className="hidden md:flex items-center gap-4">
            {user ? (
              <div className="flex items-center gap-3">
                <Link to="/mon-compte" className="flex items-center gap-2 hover:text-accent transition text-sm">
                  <div className="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center text-xs font-bold">
                    {user.prenom?.[0] ?? user.email[0].toUpperCase()}
                  </div>
                  <span>{user.prenom ?? user.email}</span>
                </Link>
                <button onClick={() => { logout(); navigate('/') }} className="hover:text-accent transition">
                  <LogOut className="w-4 h-4" />
                </button>
              </div>
            ) : (
              <div className="flex items-center gap-3 text-sm">
                <Link to="/connexion" className="hover:text-accent transition">Connexion</Link>
                <Link to="/inscription" className="bg-accent text-primary px-4 py-2 rounded-full font-semibold hover:bg-yellow-400 transition">
                  S'inscrire
                </Link>
              </div>
            )}
          </div>

          <button className="md:hidden" onClick={() => setMenuOpen(!menuOpen)}>
            {menuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>

        {menuOpen && (
          <div className="md:hidden border-t border-white/20 px-4 py-4 space-y-3 text-sm">
            <Link to="/catalogue" onClick={() => setMenuOpen(false)} className="block hover:text-accent">Catalogue</Link>
            {user && <Link to="/ma-boutique" onClick={() => setMenuOpen(false)} className="block hover:text-accent">Ma boutique</Link>}
            {user && <Link to="/commandes" onClick={() => setMenuOpen(false)} className="block hover:text-accent">Commandes</Link>}
            {user ? (
              <>
                <Link to="/mon-compte" onClick={() => setMenuOpen(false)} className="block hover:text-accent">Mon compte</Link>
                <button onClick={() => { logout(); navigate('/'); setMenuOpen(false) }} className="block hover:text-accent">Déconnexion</button>
              </>
            ) : (
              <>
                <Link to="/connexion" onClick={() => setMenuOpen(false)} className="block hover:text-accent">Connexion</Link>
                <Link to="/inscription" onClick={() => setMenuOpen(false)} className="block hover:text-accent">S'inscrire</Link>
              </>
            )}
          </div>
        )}
      </header>
      <main className="max-w-7xl mx-auto px-4 py-8">
        <Outlet />
      </main>
      <footer className="bg-primary text-white/70 text-center py-6 text-sm mt-12">
        © {new Date().getFullYear()} Gmarket — Marketplace C2C Mode
      </footer>
    </div>
  )
}
