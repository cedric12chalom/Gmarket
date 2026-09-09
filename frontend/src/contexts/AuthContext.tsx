import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { authApi } from '@/services/api';
import { User } from '@/types';

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (data: any) => Promise<{ email_verifie?: boolean }>;
  verifyEmail: (email: string, code: string) => Promise<void>;
  resendVerification: (email: string) => Promise<void>;
  logout: () => void;
  isAuthenticated: boolean;
  isProducteur: boolean;
  isAcheteur: boolean;
  isLivreur: boolean;
  isAdmin: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    const storedUser = localStorage.getItem('user');
    const token = localStorage.getItem('token');
    if (storedUser && token) {
      setUser(JSON.parse(storedUser));
    }
    setLoading(false);
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const response = await authApi.login(email, password);
    const { token, user } = response.data;
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));
    setUser(user);
    navigate(getDashboardRoute(user.role));
  }, [navigate]);

  const register = useCallback(async (data: any) => {
    const response = await authApi.register(data);
    const { token, user, email_verifie } = response.data;
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));
    setUser(user);
    return { email_verifie };
  }, []);

  const verifyEmail = useCallback(async (email: string, code: string) => {
    const response = await authApi.verifyEmail(email, code);
    const { token, user } = response.data;
    localStorage.setItem('token', token);
    localStorage.setItem('user', JSON.stringify(user));
    setUser(user);
    navigate(getDashboardRoute(user.role));
  }, [navigate]);

  const resendVerification = useCallback(async (email: string) => {
    await authApi.resendVerification(email);
  }, []);

  const logout = useCallback(() => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setUser(null);
    navigate('/login');
  }, [navigate]);

  const getDashboardRoute = (role: string): string => {
    switch (role) {
      case 'producteur': return '/producteur';
      case 'acheteur': return '/catalogue';
      case 'livreur': return '/livreur';
      case 'admin': return '/admin'; // Symfony
      default: return '/';
    }
  };

  const value: AuthContextType = {
    user,
    loading,
    login,
    register,
    verifyEmail,
    resendVerification,
    logout,
    isAuthenticated: !!user,
    isProducteur: user?.role === 'producteur',
    isAcheteur: user?.role === 'acheteur',
    isLivreur: user?.role === 'livreur',
    isAdmin: user?.role === 'admin',
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuthContext = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuthContext must be used within AuthProvider');
  return context;
};
