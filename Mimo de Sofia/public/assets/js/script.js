
// Lista com os caminhos das imagens.
const imagens = [
    "assets/images/cozinha/bolsa-marmita1.jpeg",
    "assets/images/cozinha/bolsa-marmita2.jpeg",
    "assets/images/cozinha/bolsa-marmita3.jpeg"
];

// Número da imagem atual.
let imagemAtual = 0;

// Quantidade de imagens.
const totalDeImagens = imagens.length;

// Elementos HTML.
const imagemHero = document.getElementById("hero-image");
const botaoAnterior = document.getElementById("button-anterior");
const botaoProximo = document.getElementById("button-proxima");

// Mostra a imagem atual.
function mostrarImagem() {
    imagemHero.src = imagens[imagemAtual];
}

// Próxima imagem.
function proximaImagem() {

    imagemAtual++;

    if (imagemAtual >= totalDeImagens) {
        imagemAtual = 0;
    }

    mostrarImagem();
}

// Imagem anterior.
function imagemAnterior() {

    imagemAtual--;

    if (imagemAtual < 0) {
        imagemAtual = totalDeImagens - 1;
    }

    mostrarImagem();
}

// Eventos dos botões.
botaoProximo.addEventListener("click", proximaImagem);
botaoAnterior.addEventListener("click", imagemAnterior);
