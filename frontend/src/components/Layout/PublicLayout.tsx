import React from 'react';
import { Link } from 'react-router-dom';
import { Outlet } from 'react-router-dom';
import { Leaf, LogOut } from 'lucide-react';
import { useAuthContext } from '@/contexts/AuthContext';
import { Footer } from './Footer';

export const PublicLayout: React.FC = () => {
  const { isAuthenticated, logout } = useAuthContext();

  return (
    <div className="min-h-screen flex flex-col">
      {/* Navbar publique légère — distincte de la Navbar applicative du dashboard */}
      <header className="sticky top-0 z-50 bg-[#0A4D3C] text-white shadow-md">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
          <Link to="/" className="flex items-center gap-2.5 group">
            <div className="w-10 h-10 bg-[#9FE870] rounded-2xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
              <Leaf className="w-6 h-6 text-[#07362A]" />
            </div>
            <div className="flex flex-col">
              <span className="font-sans text-xl font-extrabold tracking-tight text-white leading-none">
                Terra<span className="text-[#9FE870]">Link</span>
              </span>
              <span className="hidden sm:block text-[10px] text-emerald-200 font-medium tracking-wider uppercase">Direct Producteurs</span>
            </div>
          </Link>

          <div className="flex items-center gap-2">
            {isAuthenticated ? (
              <>
                <Link to="/catalogue" className="px-4 py-2 text-xs font-semibold text-white hover:text-[#9FE870] transition-colors">
                  Mon espace
                </Link>
                <button
                  onClick={() => logout()}
                  className="px-4 py-2 bg-[#9FE870] text-[#07362A] text-xs font-bold rounded-full hover:bg-[#8EE25B] transition-all shadow-sm flex items-center gap-1.5"
                >
                  <LogOut className="w-3.5 h-3.5" />
                  Déconnexion
                </button>
              </>
            ) : (
              <>
                <Link to="/login" className="px-4 py-2 text-xs font-semibold text-white hover:text-[#9FE870] transition-colors">
                  Connexion
                </Link>
                <Link to="/register" className="px-4 py-2 bg-[#9FE870] text-[#07362A] text-xs font-bold rounded-full hover:bg-[#8EE25B] transition-all shadow-sm">
                  S'inscrire
                </Link>
              </>
            )}
          </div>
        </div>
      </header>

      <main className="flex-1">
        <Outlet />
      </main>

      <Footer />
    </div>
  );
};
