import React, { useEffect, useRef, useState } from "react";
import { Html5Qrcode } from "html5-qrcode";
import { Scan, X, Leaf, Loader2 } from "lucide-react";
import { lotApi } from "@/services/api";
import { formatPrice, formatDate, freshnessLabel } from "@/utils/helpers";

export const QRScanner: React.FC = () => {
  const [scanning, setScanning] = useState(false);
  const [result, setResult] = useState<any>(null);
  const [error, setError] = useState("");
  const [initializing, setInitializing] = useState(true);
  const scannerRef = useRef<Html5Qrcode | null>(null);

  // Traiter le param?tre ?q= si pr?sent dans l'URL (scan depuis t?l?phone)
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const q = params.get("q");
    if (q) {
      lotApi
        .scanQR(q)
        .then((r) => {
          setResult(r.data);
          setInitializing(false);
        })
        .catch(() => {
          setError("QR Code non reconnu");
          setInitializing(false);
        });
    } else {
      setInitializing(false);
    }
  }, []);

  const startScan = async () => {
    setScanning(true);
    setResult(null);
    setError("");
    try {
      const scanner = new Html5Qrcode("qr-reader");
      scannerRef.current = scanner;
      await scanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        async (decodedText) => {
          try {
            // Si le QR contient une URL /scanner?q=..., extraire le code
            const match = decodedText.match(/[?&]q=([^&]+)/);
            const code = match ? match[1] : decodedText;
            const response = await lotApi.scanQR(code);
            setResult(response.data);
            stopScan();
          } catch {
            setError("QR Code non reconnu");
          }
        },
        () => {}
      );
    } catch {
      setError("Impossible d'acceder a la camera");
      setScanning(false);
    }
  };

  const stopScan = () => {
    if (scannerRef.current) {
      scannerRef.current.stop().then(() => {
        scannerRef.current = null;
        setScanning(false);
      });
    }
  };

  useEffect(() => {
    return () => {
      if (scannerRef.current) scannerRef.current.stop();
    };
  }, []);

  if (initializing) {
    return (
      <div className="flex justify-center items-center py-40">
        <Loader2 className="w-8 h-8 text-primary-600 animate-spin" />
      </div>
    );
  }

  return (
    <div className="max-w-2xl mx-auto px-4 py-8">
      <h1 className="text-2xl font-bold text-earth-900 mb-6 text-center">Scanner un QR Code</h1>
      {!scanning && !result && (
        <div className="card text-center py-16">
          <div className="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <Scan className="w-10 h-10 text-primary-600" />
          </div>
          <p className="text-earth-500 mb-6">
            Scannez le QR Code d'un lot pour consulter son origine et sa tracabilite
          </p>
          <button onClick={startScan} className="btn-primary">
            Demarrer le scan
          </button>
        </div>
      )}
      {scanning && (
        <div className="card p-0 overflow-hidden">
          <div className="flex items-center justify-between p-4 border-b border-earth-200">
            <h3 className="font-semibold text-earth-900">Scan en cours...</h3>
            <button onClick={stopScan} className="p-2 hover:bg-earth-100 rounded-lg">
              <X className="w-5 h-5 text-earth-500" />
            </button>
          </div>
          <div id="qr-reader" className="w-full" />
        </div>
      )}
      {error && (
        <div className="card bg-red-50 border-red-200 text-center mt-4">
          <p className="text-red-600">{error}</p>
          <button
            onClick={() => {
              setError("");
              startScan();
            }}
            className="mt-3 text-primary-600 font-medium"
          >
            Reessayer
          </button>
        </div>
      )}
      {result && (
        <div className="card space-y-6">
          <div className="text-center">
            <div className="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <Leaf className="w-8 h-8 text-primary-600" />
            </div>
            <h2 className="text-xl font-bold text-earth-900">{result.produit?.nom || "Produit"}</h2>
            <p className="text-earth-500">{result.produit?.categorie?.nom || "Categorie"}</p>
          </div>
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div className="bg-earth-50 p-3 rounded-lg">
              <p className="text-earth-500">Producteur</p>
              <p className="font-medium text-earth-900">
                {result.producteur?.prenom || ""} {result.producteur?.nom || ""}
              </p>
            </div>
            <div className="bg-earth-50 p-3 rounded-lg">
              <p className="text-earth-500">Date de recolte</p>
              <p className="font-medium text-earth-900">{formatDate(result.date_recolte)}</p>
            </div>
            <div className="bg-earth-50 p-3 rounded-lg">
              <p className="text-earth-500">Indice de fraicheur</p>
              <p className="font-medium text-earth-900">{freshnessLabel(result.indice_fraicheur)}</p>
            </div>
            <div className="bg-earth-50 p-3 rounded-lg">
              <p className="text-earth-500">Prix</p>
              <p className="font-medium text-primary-600">{formatPrice(result.prix_producteur)}</p>
            </div>
          </div>
          <button
            onClick={() => {
              setResult(null);
              startScan();
            }}
            className="w-full btn-outline"
          >
            Scanner un autre QR Code
          </button>
        </div>
      )}
    </div>
  );
};