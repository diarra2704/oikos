# Oikos – Version mobile (Android)

Cette app Android affiche **le même site** qu’Oikos en ligne (https://oikos.sygauges.com). Elle utilise donc **la même base de données** et le même backend : aucune duplication de données, aucune API supplémentaire.

## Prérequis

- Node.js (v18+)
- Android Studio (pour compiler et signer l’APK)
- JDK 17

## Installation

```bash
cd mobile-app
npm install
npx cap add android
npx cap sync
```

## Ouvrir le projet Android

```bash
npx cap open android
```

Dans Android Studio : **Build → Build Bundle(s) / APK(s) → Build APK(s)** pour générer l’APK.

## Configuration

- L’URL du site est définie dans **`capacitor.config.ts`** (`server.url`). Par défaut : `https://oikos.sygauges.com`.
- Pour pointer vers un autre environnement (staging, local), modifiez `server.url` puis relancez `npx cap sync`.

## Résumé

| Élément        | Détail                                      |
|----------------|---------------------------------------------|
| Données        | Même base MySQL que le site web             |
| Backend        | Même Laravel (oikos.sygauges.com)           |
| App            | WebView qui charge le site en plein écran  |
| Authentification | Identique au site (sessions)              |
