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

const firebaseConfig = {
  apiKey: "COLE_AQUI_SUA_API_KEY",
  authDomain: "SEU-PROJETO.firebaseapp.com",
  projectId: "SEU-PROJETO",
  storageBucket: "SEU-PROJETO.appspot.com",
  messagingSenderId: "000000000000",
  appId: "1:000000000000:web:xxxxxxxxxxxxxxxxxxxxxx"
};

firebase.initializeApp(firebaseConfig);
