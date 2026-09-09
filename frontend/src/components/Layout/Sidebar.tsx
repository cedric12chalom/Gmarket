import React from "react";
import { Link, useLocation } from "react-router-dom";
import { ShoppingCart, Leaf, Truck, Package, MapPin, LogOut, User, ClipboardList, Wallet, X } from "lucide-react";
import { useAuthContext } from "@/contexts/AuthContext";
import { useCartContext } from "@/contexts/CartContext";

interface SidebarProps {
  isOpen: boolean;
  onClose: () => void;
}

interface NavItem {
  to: string;
  label: string;
  icon: React.FC<{ className?: string }>;
  badge?: number;
}

export const Sidebar: React.FC<SidebarProps> = ({ isOpen, onClose }) => {
  const { user, logout, isAuthenticated, isProducteur, isLivreur, isAdmin } = useAuthContext();
  const { totalItems } = useCartContext();
  const location = useLocation();

  if (!isAuthenticated) return null;

  const isActive = (path: string) => location.pathname === path;

  const navItems: NavItem[] = [
    { to: "/catalogue", label: "Catalogue", icon: Leaf as any },
    { to: "/panier", label: "Panier", icon: ShoppingCart as any, badge: totalItems },
    { to: "/commandes", label: "Mes commandes", icon: ClipboardList as any },
    { to: "/wallet", label: "Portefeuille", icon: Wallet as any },
    { to: "/map", label: "Carte", icon: MapPin as any },
  ];

  if (isProducteur) {
    navItems.push({ to: "/producteur", label: "Mes lots", icon: Package as any });
  }
  if (isLivreur) {
    navItems.push({ to: "/livreur", label: "Missions", icon: Truck as any });
  }

  const renderNavItems = () => (
    <>
      {navItems.map((item) => (
        <Link
          key={item.to}
          to={item.to}
          onClick={onClose}
          className={`flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
            isActive(item.to)
              ? "bg-primary-50 text-primary-700"
              : "text-earth-600 hover:bg-primary-50 hover:text-primary-600"
          }`}
        >
          <span className="flex items-center gap-3">
            <item.icon className="w-4 h-4" />
            {item.label}
          </span>
          {item.badge !== undefined && item.badge > 0 && (
            <span className="bg-secondary-500 text-white text-xs rounded-full px-2 py-0.5 font-bold">
              {item.badge > 9 ? "9+" : item.badge}
            </span>
          )}
        </Link>
      ))}

      {isAdmin && (
        <a
          href="http://localhost:8000/admin"
          target="_blank"
          rel="noopener noreferrer"
          onClick={onClose}
          className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-earth-600 hover:bg-primary-50 hover:text-primary-600 transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
          Administration
        </a>
      )}

      <div className="border-t border-earth-200 my-3" />

      <Link
        to="/profil"
        onClick={onClose}
        className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
          isActive("/profil") ? "bg-primary-50 text-primary-700" : "text-earth-600 hover:bg-primary-50 hover:text-primary-600"
        }`}
      >
        <User className="w-4 h-4" />
        Mon profil
      </Link>

      <button
        onClick={() => { logout(); onClose(); }}
        className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
      >
        <LogOut className="w-4 h-4" />
        Déconnexion
      </button>
    </>
  );

  return (
    <>
      {/* Mobile overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={onClose}
          aria-hidden="true"
        />
      )}

      {/* Mobile drawer */}
      <aside
        className={`fixed top-0 left-0 h-full w-64 bg-white shadow-2xl z-40 transform transition-transform duration-200 ease-in-out lg:hidden
          ${isOpen ? "translate-x-0" : "-translate-x-full"}`}
      >
        <div className="flex items-center justify-between px-4 h-16 border-b border-earth-200">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
              <Leaf className="w-4 h-4 text-white" />
            </div>
            <span className="font-bold text-earth-900">TerraLink</span>
          </div>
          <button onClick={onClose} className="p-1.5 hover:bg-earth-100 rounded-lg">
            <X className="w-5 h-5 text-earth-500" />
          </button>
        </div>
        <div className="py-4">
          <div className="px-4 mb-4">
            <div className="flex items-center gap-3 p-3 bg-earth-50 rounded-xl">
              <div className="w-9 h-9 bg-primary-100 rounded-full flex items-center justify-center">
                <User className="w-4 h-4 text-primary-600" />
              </div>
              <div className="min-w-0">
                <p className="text-sm font-medium text-earth-900 truncate">{user?.prenom} {user?.nom}</p>
                <p className="text-xs text-earth-500 capitalize">{user?.role}</p>
              </div>
            </div>
          </div>
          <nav className="space-y-1 px-3">
            {renderNavItems()}
          </nav>
        </div>
      </aside>

      {/* Desktop sidebar — always visible, in-flow */}
      <aside className="hidden lg:block w-64 flex-shrink-0 border-r border-earth-200 bg-white">
        <div className="py-4 sticky top-16">
          <div className="px-4 mb-4">
            <div className="flex items-center gap-3 p-3 bg-earth-50 rounded-xl">
              <div className="w-9 h-9 bg-primary-100 rounded-full flex items-center justify-center">
                <User className="w-4 h-4 text-primary-600" />
              </div>
              <div className="min-w-0">
                <p className="text-sm font-medium text-earth-900 truncate">{user?.prenom} {user?.nom}</p>
                <p className="text-xs text-earth-500 capitalize">{user?.role}</p>
              </div>
            </div>
          </div>
          <nav className="space-y-1 px-3">
            {renderNavItems()}
          </nav>
        </div>
      </aside>
    </>
  );
};