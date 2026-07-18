// ============================================================
// Configuração do Firebase — COLE AQUI os dados do SEU projeto.
//
// Como conseguir:
// 1. Acesse https://console.firebase.google.com e crie um projeto (grátis).
// 2. No menu lateral, vá em "Firestore Database" → "Criar banco de dados"
//    → escolha "Iniciar em modo de produção" → escolha a localização.
// 3. Vá em "Authentication" → "Sign-in method" → ative "Email/senha".
// 4. Ainda em "Authentication" → aba "Users" → "Add user" → cadastre o
//    e-mail e senha de cada administrador (ex: mfatima01@gmail.com).
// 5. Vá em "Configurações do projeto" (ícone de engrenagem) → role até
//    "Seus apps" → clique no ícone "</>" (Web) → registre um app
//    → copie o objeto "firebaseConfig" e cole substituindo o de baixo.
// 6. Em Firestore Database → aba "Regras", cole as regras do arquivo
//    FIRESTORE_RULES.txt (incluído neste zip) e publique.
// ============================================================

// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyCTgSQwT-EBEWcJu4mcRnBspTEL_QJGyPg",
  authDomain: "deliciasdemaria-b4ea9.firebaseapp.com",
  projectId: "deliciasdemaria-b4ea9",
  storageBucket: "deliciasdemaria-b4ea9.firebasestorage.app",
  messagingSenderId: "974061383706",
  appId: "1:974061383706:web:22a3cd5f795623ed9861a3",
  measurementId: "G-E6JP2B6R5M"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);
