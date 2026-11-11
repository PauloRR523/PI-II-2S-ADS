
const imagensPadrao = [
    {
        caminho: "assets/images/cozinha/bolsa-marmita1.jpeg",
        texto_alt: "Peças artesanais do Ateliê Mimo de Sofia"
    },
    {
        caminho: "assets/images/cozinha/bolsa-marmita2.jpeg",
        texto_alt: "Peças artesanais do Ateliê Mimo de Sofia"
    },
    {
        caminho: "assets/images/cozinha/bolsa-marmita3.jpeg",
        texto_alt: "Peças artesanais do Ateliê Mimo de Sofia"
    }
];

const imagemHero = document.getElementById("hero-image");
const botaoAnterior = document.getElementById("button-anterior");
const botaoProximo = document.getElementById("button-proxima");

// Número da imagem atual.
let imagemAtual = 0;

let imagens = imagensPadrao;

if (imagemHero) {

    // Recebe as imagens enviadas pelo PHP.
    try {
        const imagensCadastradas = JSON.parse(
            imagemHero.dataset.imagens || "[]"
        );

        if (
            Array.isArray(imagensCadastradas) &&
            imagensCadastradas.length > 0
        ) {
            imagens = imagensCadastradas.filter(
                imagem => imagem && typeof imagem.caminho === "string" && imagem.caminho.trim() !== ""
            );
        }

    } catch (erro) {
        imagens = imagensPadrao;
    }

    if (imagens.length === 0) {
        imagens = imagensPadrao;
    }

    // Quantidade de imagens.
    const totalDeImagens = imagens.length;

    // Mostra a imagem atual.
    function mostrarImagem() {
        imagemHero.src = imagens[imagemAtual].caminho;

        imagemHero.alt = imagens[imagemAtual].texto_alt ||
            "Peças artesanais do Ateliê Mimo de Sofia";
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
    if (botaoProximo) {
        botaoProximo.addEventListener("click", proximaImagem);
    }

    if (botaoAnterior) {
        botaoAnterior.addEventListener("click", imagemAnterior);
    }
}

