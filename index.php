<?php
require_once 'connection.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0; margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Estoque</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include __DIR__ . '/partials/nav.php'; ?>
    <main>
        <h1>Gestor de Estoque</h1>
         <div>
            <h3>Gestão de Produtos e Auditoria</h3>
            <div class="options">
                <ul class="actions" role="menu" aria-label="Opções de produtos e auditoria">
                    <li role="none">
                        <a role="menuitem" href="produtos_consultar.php">🔍 Consultar Produtos</a>
                    </li>
                    <li role="none">
                        <a role="menuitem" href="produto_consultar.php">🔍 Consultar Produto</a>
                    </li>
                    <li role="none">
                        <a role="menuitem" href="#" class="danger protected-action"
                            data-next="produtos_cadastrar.php"
                            data-action="produtos_cadastrar">➕ Cadastrar Produto</a>
                    </li>
                    <li role="none">
                        <a role="menuitem" href="#" class="danger protected-action"
                            data-next="produtos_deletar.php"
                            data-action="produtos_deletar">🗑️ Deletar Produto</a>
                    </li>
                    <li role="none">
                        <a role="menuitem" href="#" class="danger protected-action"
                            data-next="auditoria.php"
                            data-action="auditoria">📋 Auditoria</a>
                    </li>
                </ul>
                <!-- Modal de senha (inicialmente escondido) -->
                <div id="auth-modal" class="auth-modal" aria-hidden="true" style="display:none;">
                    <div class="auth-modal-backdrop"></div>
                    <div class="auth-modal-box" role="dialog" aria-modal="true" aria-labelledby="auth-title">
                        <h4 id="auth-title">Acesso restrito</h4>
                        <div id="auth-error" class="msg error" style="display:none;"></div>
                        <form id="auth-form">
                            <input type="hidden" name="next" id="auth-next" value="">
                            <input type="hidden" name="action" id="auth-action" value="">
                            <label for="auth-password" style="display:block;margin:6px 0 4px;">Senha</label>
                            <input id="auth-password" name="password" type="password" required autocomplete="current-password" style="width:100%;padding:8px;border-radius:4px;border:1px solid #333;background:var(--bg-darker);color:var(--text);">
                            <div style="display:flex;gap:8px;margin-top:10px;justify-content:flex-end;">
                                <button type="button" id="auth-cancel" class="action-btn secondary">Cancelar</button>
                                <button type="submit" class="action-btn edit">Entrar</button>
                            </div>
                        </form>
                    </div>
                </div>
                <style>
                    /* minimal modal styles (pouco intrusivo, ajuste em styles.css se preferir) */
                    .auth-modal { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; z-index:60; }
                    .auth-modal-backdrop { position:absolute; inset:0; background:rgba(0,0,0,0.6); }
                    .auth-modal-box { position:relative; width:360px; max-width:90%; background:var(--bg-darker); padding:16px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.6); z-index:70; }
                    .auth-modal-box h4 { margin:0 0 8px 0; font-size:1.05rem; }
                    .msg.error { background:#3b0b0b;color:#ffd8d8;padding:8px;border-radius:6px;margin-bottom:8px; }
                    @media (max-width:480px) { .auth-modal-box { width: 92%; } }
                </style>
                <script>
                    (function(){
                        const modal = document.getElementById('auth-modal');
                        const form = document.getElementById('auth-form');
                        const pwd = document.getElementById('auth-password');
                        const nextInput = document.getElementById('auth-next');
                        const actionInput = document.getElementById('auth-action');
                        const errBox = document.getElementById('auth-error');
                        const cancelBtn = document.getElementById('auth-cancel');

                        // abrir modal quando clicar em link protegido
                        document.querySelectorAll('.protected-action').forEach(function(el){
                            el.addEventListener('click', function(e){
                                e.preventDefault();
                                errBox.style.display = 'none';
                                errBox.textContent = '';
                                nextInput.value = el.dataset.next || 'index.php';
                                actionInput.value = el.dataset.action || '';
                                pwd.value = '';
                                modal.style.display = 'flex';
                                modal.setAttribute('aria-hidden', 'false');
                                pwd.focus();
                            });
                        });

                        cancelBtn.addEventListener('click', function(){ closeModal(); });
                        modal.addEventListener('click', function(ev){
                            if (ev.target === modal || ev.target.classList.contains('auth-modal-backdrop')) closeModal();
                        });

                        function closeModal(){
                            modal.style.display = 'none';
                            modal.setAttribute('aria-hidden', 'true');
                        }

                        form.addEventListener('submit', function(ev){
                            ev.preventDefault();
                            errBox.style.display = 'none';
                            const formData = new FormData(form);
                            fetch('auth_ajax.php', {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            }).then(resp => resp.json()).then(data => {
                                if (data && data.ok) {
                                    // autorizado: redireciona para next (pode ser relativo)
                                    window.location = data.redirect || nextInput.value || '/';
                                } else {
                                    errBox.textContent = data && data.msg ? data.msg : 'Erro de autenticação.';
                                    errBox.style.display = 'block';
                                }
                            }).catch(()=> {
                                errBox.textContent = 'Erro de rede.';
                                errBox.style.display = 'block';
                            });
                        });
                    })();
                </script>
             </div>
            </div>
        </div>

    </main>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>