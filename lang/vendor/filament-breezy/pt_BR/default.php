<?php

return [
    'passkeys' => [
        'authenticate_using_passkey' => [
            'label' => 'Entrar com chave de acesso',
        ],
        'invalid' => 'Não foi possível entrar com a chave de acesso informada.',
    ],
    'profile' => [
        'browser_sessions' => [
            'heading' => 'Sessões de Navegador',
            'subheading' => 'Gerencie suas sessões ativas.',
            'label' => 'Sessões de Navegador',
            'content' => 'Se necessário, você pode encerrar todas as outras sessões do navegador em todos os seus dispositivos. Algumas sessões recentes estão listadas abaixo; se suspeitar que sua conta foi comprometida, atualize sua senha também.',
            'device' => 'Este dispositivo',
            'last_active' => 'Último acesso',
            'logout_other_sessions' => 'Encerrar outras sessões',
            'logout_heading' => 'Encerrar outras sessões do navegador',
            'logout_description' => 'Digite sua senha para confirmar que deseja encerrar as outras sessões do navegador em todos os seus dispositivos.',
            'logout_action' => 'Encerrar outras sessões',
            'incorrect_password' => 'A senha informada está incorreta. Tente novamente.',
            'logout_success' => 'Todas as outras sessões do navegador foram encerradas com sucesso.',
        ],
        'passkeys' => [
            'heading' => 'Chaves de acesso',
            'description' => 'Gerencie chaves de acesso para entrar na sua conta sem senha e com mais segurança em seus dispositivos.',
            'create' => [
                'submit' => [
                    'label' => 'Criar',
                    'submit_label' => 'Criar e autenticar',
                ],
                'error_message' => 'Não foi possível gerar a chave de acesso.',
                'success_message' => 'Chave de acesso criada com sucesso.',
            ],
            'update' => [
                'submit' => [
                    'label' => 'Atualizar',
                ],
            ],
        ],
    ],
    'fields' => [
        'passkey_name' => 'Nome da chave de acesso',
        'last_used_at' => 'Último uso',
    ],
];
