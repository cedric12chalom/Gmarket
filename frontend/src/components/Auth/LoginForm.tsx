import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { Link } from 'react-router-dom';
import { Eye, EyeOff, Mail, Lock } from 'lucide-react';
import { useAuthContext } from '@/contexts/AuthContext';

const schema = yup.object({
  email: yup.string().email('Email invalide').required('Email requis'),
  mot_de_passe: yup.string().min(6, 'Minimum 6 caractères').required('Mot de passe requis'),
});

type FormData = yup.InferType<typeof schema>;

export const LoginForm: React.FC = () => {
  const { login } = useAuthContext();
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const { register, handleSubmit, formState: { errors } } = useForm<FormData>({
    resolver: yupResolver(schema),
  });

  const onSubmit = async (data: FormData) => {
    setLoading(true);
    try {
      await login(data.email, data.mot_de_passe);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-earth-50 px-4">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-earth-900">Bienvenue sur <span className="text-primary-600">TerraLink</span></h1>
          <p className="text-earth-500 mt-2">Connectez-vous pour accéder à votre espace</p>
        </div>

        <div className="card">
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            <div>
              <label className="label">Email</label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                <input
                  {...register('email')}
                  type="email"
                  className="input-field pl-10"
                  placeholder="votre@email.com"
                />
              </div>
              {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email.message}</p>}
            </div>

            <div>
              <label className="label">Mot de passe</label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                <input
                  {...register('mot_de_passe')}
                  type={showPassword ? 'text' : 'password'}
                  className="input-field pl-10 pr-10"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-earth-400 hover:text-earth-600"
                >
                  {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                </button>
              </div>
              {errors.mot_de_passe && <p className="text-red-500 text-xs mt-1">{errors.mot_de_passe.message}</p>}
            </div>

            <div className="flex items-center justify-between text-sm">
              <label className="flex items-center gap-2 text-earth-600">
                <input type="checkbox" className="rounded border-earth-300 text-primary-600 focus:ring-primary-500" />
                Se souvenir de moi
              </label>
              <a href="#" className="text-primary-600 hover:text-primary-700">Mot de passe oublié ?</a>
            </div>

            <button type="submit" disabled={loading} className="w-full btn-primary flex items-center justify-center gap-2">
              {loading ? (
                <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
              ) : 'Se connecter'}
            </button>
          </form>

          <div className="mt-6 text-center text-sm text-earth-500">
            Pas encore de compte ?{' '}
            <Link to="/register" className="text-primary-600 font-medium hover:text-primary-700">
              S'inscrire
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};
