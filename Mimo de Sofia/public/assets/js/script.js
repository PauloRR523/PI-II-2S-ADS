
// Número da imagem que está sendo exibida.
let imagemAtual = 1;

// Quantidade total de imagens do carrossel.
const totalDeImagens = 3;

// Localiza os elementos do HTML.
const imagemHero = document.getElementById("hero-image");
const botaoAnterior = document.getElementById("button-anterior");
const botaoProximo = document.getElementById("button-proxima");

// Altera a imagem exibida.
function mostrarImagem() {
    imagemHero.src = "assets/images/hero-" + imagemAtual + ".jpg";
}

// Passa para a próxima imagem.
function proximaImagem() {
    imagemAtual++;

    if (imagemAtual > totalDeImagens) {
        imagemAtual = 1;
    }

    mostrarImagem();
}

// Volta para a imagem anterior.
function imagemAnterior() {
    imagemAtual--;

    if (imagemAtual < 1) {
        imagemAtual = totalDeImagens;
    }

    mostrarImagem();
}

// Eventos dos botões.
botaoProximo.addEventListener("click", proximaImagem);
botaoAnterior.addEventListener("click", imagemAnterior);

