/* ==========================================================================
   produtos.js — modal com galeria de fotos nas páginas de categoria
   Usa o elemento nativo <dialog>, que já cuida de ESC, bloqueio do fundo e
   foco dentro do modal.

   Como funciona:
   - Cada <article class="card"> pode ter data-fotos="foto1.jpg, foto2.jpg, ..."
     (separadas por vírgula, sem vírgula dentro do nome do arquivo).
     Sem data-fotos, o modal mostra só a imagem do próprio card.
   - Título = <h2> do card. Descrição = <p> do card, ou data-descricao se existir.
   ========================================================================== */

(function () {
    'use strict';

    const modal = document.getElementById('modal-produto');
    const listaCards = document.querySelector('.container-cards');

    if (!modal || !listaCards) return;

    const imagem = document.getElementById('modal-imagem');
    const titulo = document.getElementById('modal-titulo');
    const descricao = modal.querySelector('.modal-descricao');
    const contador = modal.querySelector('.modal-contador');
    const moldura = modal.querySelector('.modal-imagem-principal');
    const miniaturas = modal.querySelector('.modal-miniaturas');
    const botaoAnterior = modal.querySelector('.modal-anterior');
    const botaoProxima = modal.querySelector('.modal-proxima');
    const botaoFechar = modal.querySelector('.modal-fechar');
    const botaoEncomendar = modal.querySelector('.modal-encomendar');

    let fotos = [];
    let indice = 0;
    let gatilho = null; // elemento que abriu o modal (para devolver o foco)

    /* ---------- Dados do card ---------- */

    function lerFotos(card) {
        const lista = (card.dataset.fotos || '')
            .split(',')
            .map(function (item) { return item.trim(); })
            .filter(Boolean);

        if (lista.length === 0) {
            const img = card.querySelector('img');
            if (img) lista.push(img.getAttribute('src'));
        }

        return lista;
    }

    function lerDescricao(card) {
        if (card.dataset.descricao) return card.dataset.descricao;

        const p = card.querySelector('p');
        return p ? p.textContent.replace(/\s+/g, ' ').trim() : '';
    }

    /* ---------- Galeria ---------- */

    function montarMiniaturas() {
        miniaturas.innerHTML = '';

        fotos.forEach(function (src, n) {
            const item = document.createElement('li');
            const botao = document.createElement('button');
            const img = document.createElement('img');

            botao.type = 'button';
            botao.className = 'modal-miniatura';
            botao.setAttribute('aria-label', 'Ver foto ' + (n + 1));
            botao.addEventListener('click', function () { mostrar(n); });

            img.src = src;
            img.alt = '';
            img.loading = 'lazy';

            botao.appendChild(img);
            item.appendChild(botao);
            miniaturas.appendChild(item);
        });
    }

    function mostrar(n) {
        indice = (n + fotos.length) % fotos.length;

        imagem.src = fotos[indice];
        imagem.alt = titulo.textContent + ' — foto ' + (indice + 1) + ' de ' + fotos.length;
        contador.textContent = (indice + 1) + ' / ' + fotos.length;

        miniaturas.querySelectorAll('.modal-miniatura').forEach(function (botao, i) {
            botao.setAttribute('aria-current', i === indice ? 'true' : 'false');
        });
    }

    /* ---------- Abrir e fechar ---------- */

    function abrir(card, origem) {
        gatilho = origem;
        fotos = lerFotos(card);

        if (fotos.length === 0) return;

        titulo.textContent = card.querySelector('h2') ? card.querySelector('h2').textContent.trim() : '';
        descricao.textContent = lerDescricao(card);

        // Com uma foto só, esconde setas, contador e miniaturas
        modal.classList.toggle('modal-unica', fotos.length < 2);

        montarMiniaturas();
        mostrar(0);
        modal.showModal();
    }

    // Clique no botão "Ver produto" ou na foto do card
    listaCards.addEventListener('click', function (evento) {
        const card = evento.target.closest('.card');
        if (!card) return;

        const botao = evento.target.closest('.button-mais');
        const foto = evento.target.closest('img');
        if (!botao && !foto) return;

        evento.preventDefault();
        abrir(card, botao || card.querySelector('.button-mais'));
    });

    botaoFechar.addEventListener('click', function () { modal.close(); });

    // Clique no fundo escuro (o próprio <dialog>) fecha
    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) modal.close();
    });

    modal.addEventListener('close', function () {
        imagem.removeAttribute('src');
        if (gatilho) gatilho.focus();
    });

    /* ---------- Navegação ---------- */

    botaoAnterior.addEventListener('click', function () { mostrar(indice - 1); });
    botaoProxima.addEventListener('click', function () { mostrar(indice + 1); });

    modal.addEventListener('keydown', function (evento) {
        if (fotos.length < 2) return;

        if (evento.key === 'ArrowLeft') mostrar(indice - 1);
        if (evento.key === 'ArrowRight') mostrar(indice + 1);
    });

    // Deslizar o dedo para trocar de foto no celular
    let inicioX = null;

    moldura.addEventListener('touchstart', function (evento) {
        inicioX = evento.touches[0].clientX;
    }, { passive: true });

    moldura.addEventListener('touchend', function (evento) {
        if (inicioX === null || fotos.length < 2) return;

        const deslocamento = evento.changedTouches[0].clientX - inicioX;
        inicioX = null;

        if (Math.abs(deslocamento) > 50) {
            mostrar(indice + (deslocamento < 0 ? 1 : -1));
        }
    });

    /* ---------- Encomendar este produto ---------- */

    botaoEncomendar.addEventListener('click', function () {
        const nome = titulo.textContent;

        gatilho = null; // não devolver o foco ao botão do card
        modal.close();

        const form = document.querySelector('.contato-form');
        if (!form) return;

        const mensagem = form.querySelector('.form-textarea');

        if (mensagem && !mensagem.value.trim()) {
            mensagem.value = 'Olá! Tenho interesse no produto "' + nome + '".';
        }

        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (mensagem) mensagem.focus({ preventScroll: true });
    });
})();