import React, { useState } from "react";
import { useForm } from "react-hook-form";
import { User, Mail, Phone } from "lucide-react";
import { useAuthContext } from "@/contexts/AuthContext";
import { utilisateurApi } from "@/services/api";
import { toast } from "react-toastify";

export const ProfilForm: React.FC = () => {
  const { user, logout } = useAuthContext();
  const [loading, setLoading] = useState(false);
  const { register, handleSubmit } = useForm({
    defaultValues: { nom: user?.nom || "", prenom: user?.prenom || "", email: user?.email || "", telephone: user?.telephone || "" }
  });

  const onSubmit = async (data: any) => {
    setLoading(true);
    try { await utilisateurApi.updateProfile(data); toast.success("Profil mis à jour !"); }
    catch (error) { toast.error("Erreur lors de la mise à jour"); }
    finally { setLoading(false); }
  };

  return (
    <div className="max-w-2xl mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold text-earth-900 mb-6">Mon profil</h1>
      <div className="card space-y-6">
        <div className="flex items-center gap-4 pb-6 border-b border-earth-200">
          <div className="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center">
            <span className="text-2xl font-bold text-primary-700">{user?.prenom?.[0]}{user?.nom?.[0]}</span>
          </div>
          <div>
            <h2 className="text-xl font-semibold text-earth-900">{user?.prenom} {user?.nom}</h2>
            <p className="text-earth-500 capitalize">{user?.role}</p>
            <span className={`badge mt-1 ${user?.statut === "actif" ? "badge-success" : "badge-danger"}`}>{user?.statut === "actif" ? "Compte actif" : "Compte suspendu"}</span>
          </div>
        </div>
        {user?.role === 'acheteur' && (
          <div className="rounded-lg border border-earth-200 bg-earth-50 p-3 text-sm text-earth-600">
            Position de livraison définie à l'inscription — elle est enregistrée de façon définitive et ne peut
            pas être modifiée.
            {user.latitude != null && user.longitude != null && (
              <span className="block mt-1 text-earth-500">Coordonnées : {user.latitude.toFixed(5)}, {user.longitude.toFixed(5)}</span>
            )}
          </div>
        )}
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><label className="label">Nom</label><div className="relative"><User className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" /><input {...register("nom")} className="input-field pl-10" /></div></div>
            <div><label className="label">Prénom</label><div className="relative"><User className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" /><input {...register("prenom")} className="input-field pl-10" /></div></div>
          </div>
          <div><label className="label">Email</label><div className="relative"><Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" /><input {...register("email")} type="email" className="input-field pl-10" disabled /></div></div>
          <div><label className="label">Téléphone</label><div className="relative"><Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" /><input {...register("telephone")} className="input-field pl-10" /></div></div>
          <div className="pt-4 flex gap-3">
            <button type="submit" disabled={loading} className="btn-primary flex-1">{loading ? "Enregistrement..." : "Enregistrer les modifications"}</button>
            <button type="button" onClick={logout} className="btn-outline border-red-300 text-red-600 hover:bg-red-50">Déconnexion</button>
          </div>
        </form>
      </div>
    </div>
  );
};
