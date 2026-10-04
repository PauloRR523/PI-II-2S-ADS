// Número da imagem que está sendo exibida.
let imagemAtual = 1;

// Quantidade total de imagens do carrossel.
const totalDeImagens = 3;

// Localiza os elementos do HTML que serão utilizados.
const imagemHero = document.getElementById("hero-image");
const botaoAnterior = document.getElementById("previous-button");
const botaoProximo = document.getElementById("next-button");


// Altera a imagem exibida.
function mostrarImagem() {
    imagemHero.src = "images/hero-" + imagemAtual + ".jpg";
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


// Quando o botão direito for clicado,
// executa a função proximaImagem().
botaoProximo.addEventListener("click", proximaImagem);


// Quando o botão esquerdo for clicado,
// executa a função imagemAnterior().
botaoAnterior.addEventListener("click", imagemAnterior);