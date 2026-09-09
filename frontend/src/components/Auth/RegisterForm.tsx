import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import { Link } from 'react-router-dom';
import { Eye, EyeOff, User, Mail, Phone, Lock, MapPin, Truck, ShieldCheck, Smartphone, CheckCircle, ArrowLeft, Loader2 } from 'lucide-react';
import { toast } from 'react-toastify';
import { useAuthContext } from '@/contexts/AuthContext';
import { UserRole } from '@/types';
import { PositionPicker } from '@/components/GeoMap/PositionPicker';

const schema = yup.object({
  nom: yup.string().required('Nom requis'),
  prenom: yup.string().required('Prénom requis'),
  email: yup.string().email('Email invalide').required('Email requis'),
  telephone: yup.string().required('Téléphone requis'),
  mot_de_passe: yup.string()
    .required('Mot de passe requis')
    .min(8, 'Minimum 8 caractères')
    .test('complexite', 'Le mot de passe doit contenir au moins une lettre, un chiffre et un caractère spécial', (value) => {
      if (!value) return true;
      return /[A-Za-z]/.test(value) && /[0-9]/.test(value) && /[^A-Za-z0-9]/.test(value);
    }),
  confirm_mot_de_passe: yup.string().oneOf([yup.ref('mot_de_passe')], 'Les mots de passe ne correspondent pas'),
  cgu_accepte: yup.boolean().oneOf([true], 'Vous devez accepter les conditions d\'utilisation').required(),
  role: yup.string().oneOf(['producteur', 'acheteur', 'livreur']).required('Rôle requis'),
  ville: yup.string().required('Ville requise'),
  quartier: yup.string(),
  localisation: yup.string().when('role', {
    is: 'producteur',
    then: (schema) => schema.required('Localisation requise'),
  }),
  latitude: yup.string().when('role', {
    is: 'acheteur',
    then: (schema) => schema.required('Votre position de livraison est obligatoire. Sans elle, seul le mode retrait sera disponible, pas la livraison.'),
  }),
  longitude: yup.string().when('role', {
    is: 'acheteur',
    then: (schema) => schema.required('Votre position de livraison est obligatoire. Sans elle, seul le mode retrait sera disponible, pas la livraison.'),
  }),
  adresse_livraison: yup.string().when('role', {
    is: 'acheteur',
    then: (schema) => schema.required('Adresse de livraison requise'),
  }),
  type_transport: yup.string().when('role', {
    is: 'livreur',
    then: (schema) => schema.required('Type de transport requis'),
  }),
  moyen_deplacement: yup.string().when('role', {
    is: 'livreur',
    then: (schema) => schema.required('Moyen de déplacement requis'),
  }),
  cni_numero: yup.string().when('role', {
    is: 'livreur',
    then: (schema) => schema.required('Numéro de CNI requis'),
  }),
  cni_photo: yup.string(),
  photo_profil: yup.string(),
  vehicule_plaque: yup.string(),
  vehicule_marque_modele: yup.string(),
  mobile_money_numero: yup.string().when('role', {
    is: 'livreur',
    then: (schema) => schema.required('Numéro Mobile Money requis'),
  }),
  contact_urgence_nom: yup.string().when('role', {
    is: 'livreur',
    then: (schema) => schema.required('Nom du contact d\'urgence requis'),
  }),
  contact_urgence_telephone: yup.string().when('role', {
    is: 'livreur',
    then: (schema) => schema.required('Téléphone du contact d\'urgence requis'),
  }),
  conditions_livraison_accepte: yup.boolean().when('role', {
    is: 'livreur',
    then: (schema) => schema.oneOf([true], 'Vous devez accepter les conditions de livraison').required(),
  }),
});

type FormData = yup.InferType<typeof schema>;

export const RegisterForm: React.FC = () => {
  const { register: registerUser, verifyEmail, resendVerification } = useAuthContext();
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [step, setStep] = useState<'register' | 'verify'>('register');
  const [registeredEmail, setRegisteredEmail] = useState('');
  const [verificationCode, setVerificationCode] = useState('');
  const [verifyLoading, setVerifyLoading] = useState(false);
  const [resendCooldown, setResendCooldown] = useState(0);
  const { register, handleSubmit, watch, setValue, formState: { errors } } = useForm<FormData>({
    resolver: yupResolver(schema),
    defaultValues: { role: 'acheteur', cgu_accepte: false, conditions_livraison_accepte: false },
  });

  const role = watch('role');
  const localisation = watch('localisation');
  const latitude = watch('latitude');
  const longitude = watch('longitude');
  const moyenDeplacement = watch('moyen_deplacement');
  const motDePasse = watch('mot_de_passe') || '';

  const passwordChecks = [
    { label: '8 caractères minimum', ok: motDePasse.length >= 8 },
    { label: 'Au moins une lettre', ok: /[A-Za-z]/.test(motDePasse) },
    { label: 'Au moins un chiffre', ok: /[0-9]/.test(motDePasse) },
    { label: 'Au moins un caractère spécial', ok: /[^A-Za-z0-9]/.test(motDePasse) },
  ];

  useEffect(() => {
    if (moyenDeplacement === 'pied') {
      setValue('vehicule_plaque', '');
      setValue('vehicule_marque_modele', '');
    } else if (moyenDeplacement === 'velo') {
      setValue('vehicule_plaque', '');
    }
  }, [moyenDeplacement, setValue]);

  useEffect(() => {
    if (resendCooldown <= 0) return;
    const timer = setTimeout(() => setResendCooldown(resendCooldown - 1), 1000);
    return () => clearTimeout(timer);
  }, [resendCooldown]);

  const onSubmit = async (data: FormData) => {
    setLoading(true);
    try {
      const result = await registerUser(data);
      setRegisteredEmail(data.email);
      if (result.email_verifie === false) {
        setStep('verify');
        toast.info('Un code de vérification a été envoyé à votre adresse email.');
      }
    } catch {
      // error already toasted by interceptor
    } finally {
      setLoading(false);
    }
  };

  const handleVerify = async () => {
    if (verificationCode.length !== 6) {
      toast.error('Le code doit contenir 6 chiffres.');
      return;
    }
    setVerifyLoading(true);
    try {
      await verifyEmail(registeredEmail, verificationCode);
      toast.success('Email vérifié ! Bienvenue sur TerraLink.');
    } catch {
      // error already toasted
    } finally {
      setVerifyLoading(false);
    }
  };

  const handleResend = async () => {
    try {
      await resendVerification(registeredEmail);
      toast.success('Un nouveau code a été envoyé.');
      setResendCooldown(60);
    } catch {
      // error already toasted
    }
  };

  const roleOptions: { value: UserRole; label: string; icon: typeof User }[] = [
    { value: 'acheteur', label: 'Acheteur', icon: User },
    { value: 'producteur', label: 'Producteur', icon: MapPin },
    { value: 'livreur', label: 'Livreur', icon: Truck },
  ];

  return (
    <div className="min-h-screen flex items-center justify-center bg-earth-50 px-4 py-8">
      <div className="w-full max-w-lg">
        {step === 'register' ? (
          <>
            <div className="text-center mb-8">
              <h1 className="text-3xl font-bold text-earth-900">Créer un compte <span className="text-primary-600">TerraLink</span></h1>
              <p className="text-earth-500 mt-2">Rejoignez la plateforme agricole du Cameroun</p>
            </div>

        <div className="card">
          <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">
            <div>
              <label className="label">Je suis un</label>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {roleOptions.map((opt) => (
                  <label
                    key={opt.value}
                    className={`cursor-pointer rounded-lg border-2 p-3 text-center transition-all ${
                      role === opt.value
                        ? 'border-primary-600 bg-primary-50 text-primary-700'
                        : 'border-earth-200 hover:border-earth-300 text-earth-600'
                    }`}
                  >
                    <input type="radio" {...register('role')} value={opt.value} className="sr-only" />
                    <opt.icon className="w-5 h-5 mx-auto mb-1" />
                    <span className="text-sm font-medium">{opt.label}</span>
                  </label>
                ))}
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="label">Nom</label>
                <input {...register('nom')} className="input-field" placeholder="Kaboré" />
                {errors.nom && <p className="text-red-500 text-xs mt-1">{errors.nom.message}</p>}
              </div>
              <div>
                <label className="label">Prénom</label>
                <input {...register('prenom')} className="input-field" placeholder="Cédric" />
                {errors.prenom && <p className="text-red-500 text-xs mt-1">{errors.prenom.message}</p>}
              </div>
            </div>

            <div>
              <label className="label">Email</label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                <input {...register('email')} type="email" className="input-field pl-10" placeholder="votre@email.com" />
              </div>
              {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email.message}</p>}
            </div>

            <div>
              <label className="label">Téléphone</label>
              <div className="relative">
                <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                <input {...register('telephone')} className="input-field pl-10" placeholder="+237 6XX XXX XXX" />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="label">Ville</label>
                <div className="relative">
                  <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                  <input {...register('ville')} className="input-field pl-10" placeholder="Yaoundé" />
                </div>
                {errors.ville && <p className="text-red-500 text-xs mt-1">{errors.ville.message}</p>}
              </div>
              <div>
                <label className="label">Quartier</label>
                <input {...register('quartier')} className="input-field" placeholder="Optionnel" />
              </div>
            </div>

            {role === 'producteur' && (
              <div>
                <label className="label">Localisation de l'exploitation</label>
                <div className="relative">
                  <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                  <input {...register('localisation')} className="input-field pl-10" placeholder="Yaoundé, région du Centre" />
                </div>
                {errors.localisation && <p className="text-red-500 text-xs mt-1">{errors.localisation.message}</p>}
              </div>
            )}

            {role === 'acheteur' && (
              <div className="space-y-5 rounded-xl border border-earth-200 bg-earth-50/60 p-4">
                <div className="flex items-center gap-2">
                  <MapPin className="w-5 h-5 text-primary-600" />
                  <p className="text-sm font-semibold text-earth-900">Position de livraison</p>
                </div>
                <p className="text-xs text-earth-600">
                  Votre position est nécessaire au routage de vos livraisons. Elle est enregistrée de façon
                  définitive à l'inscription et ne pourra plus être modifiée. Sans position, seul le mode
                  retrait sera disponible (pas de livraison).
                </p>

                <PositionPicker
                  value={{
                    latitude: latitude ? Number(latitude) : null,
                    longitude: longitude ? Number(longitude) : null,
                  }}
                  onChange={({ latitude: lat, longitude: lng }) => {
                    setValue('latitude', lat != null ? String(lat) : '', { shouldValidate: true });
                    setValue('longitude', lng != null ? String(lng) : '', { shouldValidate: true });
                  }}
                  description={localisation || ''}
                  onDescriptionChange={(v) => setValue('localisation', v, { shouldValidate: true })}
                  descriptionLabel="Adresse de livraison (précisions)"
                  descriptionPlaceholder="Ex. Yaoundé, quartier Bastos, rue du Plateau, immeuble bleu"
                />
                {(errors.latitude || errors.longitude) && (
                  <p className="text-red-500 text-xs mt-1">
                    {errors.latitude?.message || errors.longitude?.message}
                  </p>
                )}
                {!latitude && !longitude && (
                  <p className="text-amber-600 text-xs mt-1">
                    Sans position GPS, seules les commandes en retrait seront possibles. Positionnez-vous sur la
                    carte ou autorisez la géolocalisation.
                  </p>
                )}

                <div>
                  <label className="label">Adresse de livraison</label>
                  <div className="relative">
                    <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                    <input {...register('adresse_livraison')} className="input-field pl-10" placeholder="Quartier, Ville" />
                  </div>
                  {errors.adresse_livraison && <p className="text-red-500 text-xs mt-1">{errors.adresse_livraison.message}</p>}
                </div>
              </div>
            )}

            {role === 'livreur' && (
              <div className="space-y-5 rounded-xl border border-earth-200 bg-earth-50/60 p-4">
                <div className="flex items-center gap-2">
                  <ShieldCheck className="w-5 h-5 text-primary-600" />
                  <p className="text-sm font-semibold text-earth-900">Dossier de livraison</p>
                </div>

                <PositionPicker
                  value={{
                    latitude: latitude ? Number(latitude) : null,
                    longitude: longitude ? Number(longitude) : null,
                  }}
                  onChange={({ latitude: lat, longitude: lng }) => {
                    setValue('latitude', lat != null ? String(lat) : '', { shouldValidate: true });
                    setValue('longitude', lng != null ? String(lng) : '', { shouldValidate: true });
                  }}
                  description={localisation || ''}
                  onDescriptionChange={(v) => setValue('localisation', v, { shouldValidate: true })}
                  descriptionLabel="Zone d'intervention"
                  descriptionPlaceholder="Ex. Douala, quartier Akwa, près de la Poste centrale"
                />
                {(errors.latitude || errors.longitude) && (
                  <p className="text-red-500 text-xs mt-1">
                    {errors.latitude?.message || errors.longitude?.message}
                  </p>
                )}

                <div>
                  <label className="label">Type de transport</label>
                  <div className="relative">
                    <Truck className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                    <select {...register('type_transport')} className="input-field pl-10">
                      <option value="">Sélectionner...</option>
                      <option value="standard">Standard</option>
                      <option value="rapide">Rapide</option>
                      <option value="refrigere">Réfrigéré</option>
                    </select>
                  </div>
                  {errors.type_transport && <p className="text-red-500 text-xs mt-1">{errors.type_transport.message}</p>}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="label">N° CNI</label>
                    <input {...register('cni_numero')} className="input-field" placeholder="N° de la carte nationale" />
                    {errors.cni_numero && <p className="text-red-500 text-xs mt-1">{errors.cni_numero.message}</p>}
                  </div>
                  <div>
                    <label className="label">Moyen de déplacement</label>
                    <select {...register('moyen_deplacement')} className="input-field">
                      <option value="">Sélectionner...</option>
                      <option value="pied">À pied</option>
                      <option value="velo">Vélo</option>
                      <option value="moto">Moto</option>
                      <option value="voiture">Voiture</option>
                      <option value="camionnette">Camionnette</option>
                    </select>
                    {errors.moyen_deplacement && <p className="text-red-500 text-xs mt-1">{errors.moyen_deplacement.message}</p>}
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="label">Photo CNI (URL)</label>
                    <input {...register('cni_photo')} className="input-field" placeholder="https://..." />
                  </div>
                  <div>
                    <label className="label">Photo de profil (URL)</label>
                    <input {...register('photo_profil')} className="input-field" placeholder="https://..." />
                  </div>
                </div>

                {['moto', 'voiture', 'camionnette'].includes(moyenDeplacement || '') && (
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="label">Immatriculation (Plaque)</label>
                      <input {...register('vehicule_plaque')} className="input-field" placeholder="Ex. LT 123 AB" />
                      {errors.vehicule_plaque && <p className="text-red-500 text-xs mt-1">{errors.vehicule_plaque.message}</p>}
                    </div>
                    <div>
                      <label className="label">Marque & modèle du véhicule</label>
                      <input {...register('vehicule_marque_modele')} className="input-field" placeholder="Ex. Yamaha DT 125" />
                      {errors.vehicule_marque_modele && <p className="text-red-500 text-xs mt-1">{errors.vehicule_marque_modele.message}</p>}
                    </div>
                  </div>
                )}

                {moyenDeplacement === 'velo' && (
                  <div>
                    <label className="label">Marque & modèle du vélo (optionnel)</label>
                    <input {...register('vehicule_marque_modele')} className="input-field" placeholder="Ex. VTT Decathlon, B'TWIN" />
                    {errors.vehicule_marque_modele && <p className="text-red-500 text-xs mt-1">{errors.vehicule_marque_modele.message}</p>}
                  </div>
                )}

                <div>
                  <label className="label">Numéro Mobile Money</label>
                  <div className="relative">
                    <Smartphone className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                    <input {...register('mobile_money_numero')} className="input-field pl-10" placeholder="+237 6XX XXX XXX" />
                  </div>
                  {errors.mobile_money_numero && <p className="text-red-500 text-xs mt-1">{errors.mobile_money_numero.message}</p>}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="label">Contact d'urgence — nom</label>
                    <input {...register('contact_urgence_nom')} className="input-field" placeholder="Nom du contact" />
                    {errors.contact_urgence_nom && <p className="text-red-500 text-xs mt-1">{errors.contact_urgence_nom.message}</p>}
                  </div>
                  <div>
                    <label className="label">Contact d'urgence — téléphone</label>
                    <input {...register('contact_urgence_telephone')} className="input-field" placeholder="+237 6XX XXX XXX" />
                    {errors.contact_urgence_telephone && <p className="text-red-500 text-xs mt-1">{errors.contact_urgence_telephone.message}</p>}
                  </div>
                </div>

                <div className="flex items-start gap-3 rounded-lg border border-earth-200 bg-white p-3">
                  <input type="checkbox" {...register('conditions_livraison_accepte')} className="mt-1 h-4 w-4 rounded border-earth-300 text-primary-600" />
                  <label className="text-sm text-earth-700">
                    J'accepte les conditions de livraison : horaires, zones couvertes, photos de retrait et de livraison obligatoires, responsabilité du livreur pendant le transport.
                  </label>
                </div>
                {errors.conditions_livraison_accepte && <p className="text-red-500 text-xs mt-1">{errors.conditions_livraison_accepte.message}</p>}
              </div>
            )}

            <div className="flex items-start gap-3 rounded-lg border border-earth-200 bg-earth-50 p-3">
              <input type="checkbox" {...register('cgu_accepte')} className="mt-1 h-4 w-4 rounded border-earth-300 text-primary-600" />
              <label className="text-sm text-earth-700">
                J'accepte les <a href="/cgu" target="_blank" rel="noreferrer" className="font-semibold text-primary-600 hover:text-primary-700">conditions d'utilisation</a>
              </label>
            </div>
            {errors.cgu_accepte && <p className="text-red-500 text-xs mt-1">{errors.cgu_accepte.message}</p>}

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
                <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-3 top-1/2 -translate-y-1/2 text-earth-400">
                  {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                </button>
              </div>
              {errors.mot_de_passe && <p className="text-red-500 text-xs mt-1">{errors.mot_de_passe.message}</p>}
              {motDePasse.length > 0 && (
                <div className="mt-2 rounded-lg border border-earth-200 bg-earth-50/60 p-3 space-y-1.5">
                  {passwordChecks.map((check) => (
                    <div key={check.label} className="flex items-center gap-2 text-xs">
                      <span
                        className={`flex items-center justify-center w-4 h-4 rounded-full text-[10px] font-bold transition-colors ${
                          check.ok ? 'bg-emerald-100 text-emerald-700' : 'bg-earth-200 text-earth-400'
                        }`}
                      >
                        {check.ok ? '✓' : '·'}
                      </span>
                      <span className={`${check.ok ? 'text-emerald-700' : 'text-earth-500'}`}>{check.label}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div>
              <label className="label">Confirmer le mot de passe</label>
              <input {...register('confirm_mot_de_passe')} type="password" className="input-field" placeholder="••••••••" />
              {errors.confirm_mot_de_passe && <p className="text-red-500 text-xs mt-1">{errors.confirm_mot_de_passe.message}</p>}
            </div>

            <button type="submit" disabled={loading} className="w-full btn-primary flex items-center justify-center gap-2">
              {loading ? <div className="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin" /> : "S'inscrire"}
            </button>
          </form>

          <div className="mt-6 text-center text-sm text-earth-500">
            Déjà un compte ?{' '}
            <Link to="/login" className="text-primary-600 font-medium hover:text-primary-700">Se connecter</Link>
          </div>
        </div>
          </>
        ) : (
          <>
            <div className="text-center mb-8">
              <div className="mx-auto w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center mb-4">
                <Mail className="w-8 h-8 text-primary-600" />
              </div>
              <h1 className="text-3xl font-bold text-earth-900">Vérifiez votre email</h1>
              <p className="text-earth-500 mt-2">
                Un code à 6 chiffres a été envoyé à<br />
                <span className="font-semibold text-earth-700">{registeredEmail}</span>
              </p>
            </div>

            <div className="card">
              <div className="space-y-5">
                <div>
                  <label className="label">Code de vérification</label>
                  <div className="relative">
                    <CheckCircle className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-earth-400" />
                    <input
                      type="text"
                      value={verificationCode}
                      onChange={(e) => {
                        const val = e.target.value.replace(/\D/g, '').slice(0, 6);
                        setVerificationCode(val);
                      }}
                      className="input-field pl-10 text-center text-lg tracking-[0.5em] font-mono"
                      placeholder="000000"
                      maxLength={6}
                      autoFocus
                    />
                  </div>
                </div>

                <button
                  onClick={handleVerify}
                  disabled={verifyLoading || verificationCode.length !== 6}
                  className="w-full btn-primary flex items-center justify-center gap-2"
                >
                  {verifyLoading ? <Loader2 className="w-5 h-5 animate-spin" /> : <CheckCircle className="w-5 h-5" />}
                  Vérifier
                </button>

                <div className="text-center text-sm text-earth-500">
                  Vous n'avez pas reçu le code ?{' '}
                  <button
                    type="button"
                    onClick={handleResend}
                    disabled={resendCooldown > 0}
                    className="text-primary-600 font-medium hover:text-primary-700 disabled:text-earth-400 disabled:cursor-not-allowed"
                  >
                    {resendCooldown > 0 ? `Renvoyer dans ${resendCooldown}s` : 'Renvoyer le code'}
                  </button>
                </div>

                <button
                  type="button"
                  onClick={() => { setStep('register'); setVerificationCode(''); }}
                  className="flex items-center justify-center gap-1 text-sm text-earth-500 hover:text-earth-700 w-full"
                >
                  <ArrowLeft className="w-4 h-4" />
                  Modifier l'adresse email
                </button>
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  );
};
