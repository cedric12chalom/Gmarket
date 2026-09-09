import React from 'react';
import { Leaf, Mail, Phone, MapPin, ShieldCheck, Truck, RefreshCw } from 'lucide-react';

export const Footer: React.FC = () => {
  return (
    <footer className="bg-[#07362A] text-emerald-100 border-t border-emerald-900/50">
      {/* Upper features bar */}
      <div className="border-b border-white/10 py-8 bg-[#052920]">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6 text-center md:text-left">
          <div className="flex items-center justify-center md:justify-start gap-4">
            <div className="w-12 h-12 bg-[#9FE870]/20 rounded-2xl flex items-center justify-center text-[#9FE870]">
              <Truck className="w-6 h-6" />
            </div>
            <div>
              <h5 className="font-bold text-white text-sm">Livraison Ultra-Rapide</h5>
              <p className="text-xs text-emerald-200/80">Direct de la ferme à votre domicile en 15-45min</p>
            </div>
          </div>
          <div className="flex items-center justify-center md:justify-start gap-4">
            <div className="w-12 h-12 bg-[#9FE870]/20 rounded-2xl flex items-center justify-center text-[#9FE870]">
              <ShieldCheck className="w-6 h-6" />
            </div>
            <div>
              <h5 className="font-bold text-white text-sm">Qualité & Fraîcheur Garantie</h5>
              <p className="text-xs text-emerald-200/80">Traçabilité QR Code & Indice de Fraîcheur</p>
            </div>
          </div>
          <div className="flex items-center justify-center md:justify-start gap-4">
            <div className="w-12 h-12 bg-[#9FE870]/20 rounded-2xl flex items-center justify-center text-[#9FE870]">
              <RefreshCw className="w-6 h-6" />
            </div>
            <div>
              <h5 className="font-bold text-white text-sm">Prix Direct Producteur</h5>
              <p className="text-xs text-emerald-200/80">Zero intermédiaire inutile, équité garantie</p>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div className="col-span-1 md:col-span-1">
            <div className="flex items-center gap-2.5 mb-4">
              <div className="w-10 h-10 bg-[#9FE870] rounded-2xl flex items-center justify-center">
                <Leaf className="w-6 h-6 text-[#07362A]" />
              </div>
              <span className="text-xl font-black text-white tracking-tight">Terra<span className="text-[#9FE870]">Link</span></span>
            </div>
            <p className="text-xs text-emerald-200/80 leading-relaxed mb-4">
              Plateforme numérique de commercialisation et de distribution agricole directe. Fraîcheur, transparence et livraison directe des producteurs.
            </p>
          </div>
          <div>
            <h4 className="text-white font-bold text-sm mb-4 tracking-wide uppercase">Navigation</h4>
            <ul className="space-y-2.5 text-xs text-emerald-200/80">
              <li><a href="/catalogue" className="hover:text-[#9FE870] transition-colors">Catalogue Produits</a></li>
              <li><a href="/producteur" className="hover:text-[#9FE870] transition-colors">Espace Producteur</a></li>
              <li><a href="/livreur" className="hover:text-[#9FE870] transition-colors">Espace Livreur</a></li>
              <li><a href="/scanner" className="hover:text-[#9FE870] transition-colors">Scanner QR Traçabilité</a></li>
            </ul>
          </div>
          <div>
            <h4 className="text-white font-bold text-sm mb-4 tracking-wide uppercase">Informations</h4>
            <ul className="space-y-2.5 text-xs text-emerald-200/80">
              <li><a href="/cgu" className="hover:text-[#9FE870] transition-colors">Conditions d'utilisation</a></li>
              <li><a href="#" className="hover:text-[#9FE870] transition-colors">Politique de confidentialité</a></li>
              <li><a href="#" className="hover:text-[#9FE870] transition-colors">Programme Direct Fermier</a></li>
            </ul>
          </div>
          <div>
            <h4 className="text-white font-bold text-sm mb-4 tracking-wide uppercase">Contact & Support</h4>
            <ul className="space-y-3 text-xs text-emerald-200/80">
              <li className="flex items-center gap-2.5">
                <Mail className="w-4 h-4 text-[#9FE870]" />
                contact@terralink.cm
              </li>
              <li className="flex items-center gap-2.5">
                <Phone className="w-4 h-4 text-[#9FE870]" />
                +237 6XX XXX XXX
              </li>
              <li className="flex items-center gap-2.5">
                <MapPin className="w-4 h-4 text-[#9FE870]" />
                Yaoundé, Cameroun
              </li>
            </ul>
          </div>
        </div>
        <div className="border-t border-white/10 mt-10 pt-6 text-center text-xs text-emerald-300/60">
          © {new Date().getFullYear()} TerraLink. Tous droits réservés. Épicerie Agricole Directe.
        </div>
      </div>
    </footer>
  );
};

