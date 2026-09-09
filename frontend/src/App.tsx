import React, { useState } from 'react';
import { Routes, Route, Navigate, useParams } from 'react-router-dom';
import { Layout } from '@/components/Layout/Layout';
import { PublicLayout } from '@/components/Layout/PublicLayout';
import { AuthProvider } from '@/contexts/AuthContext';
import { CartProvider } from '@/contexts/CartContext';
import { LoginForm } from '@/components/Auth/LoginForm';
import { RegisterForm } from '@/components/Auth/RegisterForm';
import { CguPage } from '@/pages/CguPage';
import { LandingPage } from '@/pages/LandingPage';
import { CataloguePage } from '@/pages/CataloguePage';
import { PanierPage } from '@/pages/PanierPage';
import { ProductDetail } from '@/components/Catalogue/ProductDetail';
import { CheckoutForm } from '@/components/Cart/CheckoutForm';
import { CartDrawer } from '@/components/Cart/CartDrawer';
import { CommandeList } from '@/components/Commandes/CommandeList';
import { CommandeDetail } from '@/components/Commandes/CommandeDetail';
import { ProducteurDashboard } from '@/components/Producteur/ProducteurDashboard';
import { LivreurDashboard } from '@/components/Livreur/LivreurDashboard';
import { QRScanner } from '@/components/QRScanner/QRScanner';
import { ProfilForm } from '@/components/Profil/ProfilForm';
import { CartePage } from '@/components/Map/CartePage';
import { GeoMap } from '@/components/GeoMap/GeoMap';
import { LitigePage } from '@/components/Litige/LitigePage';
import { DeliveryTracker } from '@/components/GeoMap/DeliveryTracker';
import { WalletPage } from '@/pages/WalletPage';
import { useAuthContext } from '@/contexts/AuthContext';
import { commandeApi } from '@/services/api';
import { useQuery } from 'react-query';

const ProtectedRoute: React.FC<{ children: React.ReactNode; allowedRoles?: string[] }> = ({ children, allowedRoles }) => {
  const { isAuthenticated, user } = useAuthContext();
  if (!isAuthenticated) return <Navigate to="/login" />;
  if (allowedRoles && !allowedRoles.includes(user!.role)) return <Navigate to="/" />;
  return <>{children}</>;
};

const CommandesPage: React.FC = () => {
  const { user } = useAuthContext();
  const { data: commandes } = useQuery('mes-commandes', () => {
    if (!user) return [];
    return commandeApi.getByAcheteur(user.id).then(r => r.data);
  }, { enabled: !!user });
  return <CommandeList commandes={commandes || []} />;
};

const DeliveryTrackerWrapper: React.FC = () => {
  const { id } = useParams() as { id: string };
  return <DeliveryTracker deliveryId={parseInt(id, 10)} />;
};

const AppContent: React.FC = () => {
  const [cartOpen, setCartOpen] = useState(false);

  return (
    <>
      <CartDrawer isOpen={cartOpen} onClose={() => setCartOpen(false)} />
      <Routes>
        {/* Pages publiques : layout minimal, sans Sidebar ni Navbar applicative */}
        <Route element={<PublicLayout />}>
          <Route path="/" element={<LandingPage />} />
          <Route path="/login" element={<LoginForm />} />
          <Route path="/register" element={<RegisterForm />} />
          <Route path="/cgu" element={<CguPage />} />
        </Route>

        {/* Pages applicatives : layout partagé avec Sidebar/Navbar du dashboard */}
        <Route element={<Layout />}>
          <Route path="/catalogue" element={<CataloguePage />} />
          <Route path="/panier" element={<PanierPage />} />
          <Route path="/lot/:id" element={<ProductDetail />} />
          <Route path="/commander" element={<ProtectedRoute><CheckoutForm /></ProtectedRoute>} />
          <Route path="/commandes" element={<ProtectedRoute><CommandesPage /></ProtectedRoute>} />
          <Route path="/commandes/:id" element={<ProtectedRoute><CommandeDetail /></ProtectedRoute>} />
          <Route path="/producteur" element={<ProtectedRoute allowedRoles={['producteur']}><ProducteurDashboard /></ProtectedRoute>} />
          <Route path="/livreur" element={<ProtectedRoute allowedRoles={['livreur']}><LivreurDashboard /></ProtectedRoute>} />
          <Route path="/scanner" element={<QRScanner />} />
          <Route path="/carte" element={<ProtectedRoute><CartePage /></ProtectedRoute>} />
          <Route path="/map" element={<ProtectedRoute><GeoMap /></ProtectedRoute>} />
          <Route path="/litiges" element={<ProtectedRoute><LitigePage /></ProtectedRoute>} />
          <Route path="/litiges/:id" element={<ProtectedRoute><LitigePage /></ProtectedRoute>} />
          <Route path="/profil" element={<ProtectedRoute><ProfilForm /></ProtectedRoute>} />
          <Route path="/wallet" element={<ProtectedRoute><WalletPage /></ProtectedRoute>} />
          <Route path="/delivery-tracker/:id" element={<DeliveryTrackerWrapper />} />
          <Route path="/admin/*" element={<Navigate to="http://localhost:8000/admin" />} />
        </Route>
      </Routes>
    </>
  );
};

const App: React.FC = () => {
  return (
    <AuthProvider>
      <CartProvider>
        <AppContent />
      </CartProvider>
    </AuthProvider>
  );
};

export default App;
