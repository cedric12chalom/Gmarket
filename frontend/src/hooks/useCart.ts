import { useState, useCallback } from 'react';
import { CartItem, Lot } from '@/types';

const CART_KEY = 'terralink_cart';

export const useCart = () => {
  const [items, setItems] = useState<CartItem[]>(() => {
    const stored = localStorage.getItem(CART_KEY);
    return stored ? JSON.parse(stored) : [];
  });

  const saveCart = useCallback((newItems: CartItem[]) => {
    setItems(newItems);
    localStorage.setItem(CART_KEY, JSON.stringify(newItems));
  }, []);

  const addItem = useCallback((lot: Lot, quantite: number) => {
    setItems((prev) => {
      const existing = prev.find((item) => item.lot.id === lot.id);
      let newItems;
      if (existing) {
        newItems = prev.map((item) =>
          item.lot.id === lot.id
            ? { ...item, quantite: Math.min(item.quantite + quantite, lot.quantite_disponible) }
            : item
        );
      } else {
        newItems = [...prev, { lot, quantite }];
      }
      localStorage.setItem(CART_KEY, JSON.stringify(newItems));
      return newItems;
    });
  }, []);

  const removeItem = useCallback((lotId: number) => {
    setItems((prev) => {
      const newItems = prev.filter((item) => item.lot.id !== lotId);
      localStorage.setItem(CART_KEY, JSON.stringify(newItems));
      return newItems;
    });
  }, []);

  const updateQuantity = useCallback((lotId: number, quantite: number) => {
    setItems((prev) => {
      const newItems = prev.map((item) =>
        item.lot.id === lotId ? { ...item, quantite } : item
      );
      localStorage.setItem(CART_KEY, JSON.stringify(newItems));
      return newItems;
    });
  }, []);

  const clearCart = useCallback(() => {
    setItems([]);
    localStorage.removeItem(CART_KEY);
  }, []);

  const totalItems = items.reduce((sum, item) => sum + item.quantite, 0);
  const totalPrice = items.reduce((sum, item) => sum + item.quantite * item.lot.prix_producteur, 0);

  return {
    items,
    addItem,
    removeItem,
    updateQuantity,
    clearCart,
    totalItems,
    totalPrice,
    saveCart,
  };
};
