import { BrowserRouter, Routes, Route } from 'react-router-dom'
import { useEffect } from 'react'
import { useAuthStore } from './stores/authStore'
import Layout from './components/Layout'
import HomePage from './pages/HomePage'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import BoutiquePage from './pages/BoutiquePage'
import ProduitPage from './pages/ProduitPage'
import CataloguePage from './pages/CataloguePage'
import MonComptePage from './pages/MonComptePage'
import MaBoutiquePage from './pages/MaBoutiquePage'
import CreerBoutiquePage from './pages/CreerBoutiquePage'
import CommandesPage from './pages/CommandesPage'

export default function App() {
  const fetchMe = useAuthStore((s) => s.fetchMe)
  useEffect(() => { fetchMe() }, [])

  return (
    <BrowserRouter>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<HomePage />} />
          <Route path="/catalogue" element={<CataloguePage />} />
          <Route path="/boutique/:slug" element={<BoutiquePage />} />
          <Route path="/produit/:id" element={<ProduitPage />} />
          <Route path="/connexion" element={<LoginPage />} />
          <Route path="/inscription" element={<RegisterPage />} />
          <Route path="/mon-compte" element={<MonComptePage />} />
          <Route path="/ma-boutique" element={<MaBoutiquePage />} />
          <Route path="/creer-boutique" element={<CreerBoutiquePage />} />
          <Route path="/commandes" element={<CommandesPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
