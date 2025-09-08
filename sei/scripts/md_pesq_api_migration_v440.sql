-- =======================================================================
-- Script de migração para o módulo Pesquisa Pública - API REST v1
-- Versão: 4.3.0 -> 4.4.0
-- =======================================================================

-- Tabela para gerenciamento de chaves de API
CREATE TABLE md_pesq_api_key (
    id_api_key INTEGER NOT NULL,
    id_contato INTEGER NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    sin_ativo CHAR(1) NOT NULL DEFAULT 'S',
    dth_criacao DATETIME NOT NULL,
    dth_ultimo_acesso DATETIME,
    dth_expiracao DATETIME,
    descricao VARCHAR(500),
    permissoes TEXT,
    CONSTRAINT pk_md_pesq_api_key PRIMARY KEY (id_api_key),
    CONSTRAINT fk_md_pesq_api_key_contato FOREIGN KEY (id_contato) REFERENCES contato (id_contato),
    CONSTRAINT ck_md_pesq_api_key_ativo CHECK (sin_ativo IN ('S','N')),
    CONSTRAINT uk_md_pesq_api_key UNIQUE (api_key)
);

-- Sequência para chaves de API
CREATE SEQUENCE seq_md_pesq_api_key START WITH 1 INCREMENT BY 1;

-- Tabela para log de uso da API
CREATE TABLE md_pesq_api_log (
    id_log INTEGER NOT NULL,
    id_api_key INTEGER,
    endpoint VARCHAR(500) NOT NULL,
    metodo VARCHAR(10) NOT NULL,
    parametros TEXT,
    dth_acesso DATETIME NOT NULL,
    ip_usuario VARCHAR(45),
    user_agent TEXT,
    codigo_resposta INTEGER,
    tempo_execucao INTEGER,
    CONSTRAINT pk_md_pesq_api_log PRIMARY KEY (id_log),
    CONSTRAINT fk_md_pesq_api_log_key FOREIGN KEY (id_api_key) REFERENCES md_pesq_api_key (id_api_key) ON DELETE SET NULL
);

-- Sequência para logs de API
CREATE SEQUENCE seq_md_pesq_api_log START WITH 1 INCREMENT BY 1;

-- Índices para melhor performance
CREATE INDEX idx_md_pesq_api_key_contato ON md_pesq_api_key (id_contato);
CREATE INDEX idx_md_pesq_api_key_ativo ON md_pesq_api_key (sin_ativo);
CREATE INDEX idx_md_pesq_api_key_ultimo_acesso ON md_pesq_api_key (dth_ultimo_acesso);

CREATE INDEX idx_md_pesq_api_log_api_key ON md_pesq_api_log (id_api_key);
CREATE INDEX idx_md_pesq_api_log_endpoint ON md_pesq_api_log (endpoint);
CREATE INDEX idx_md_pesq_api_log_metodo ON md_pesq_api_log (metodo);
CREATE INDEX idx_md_pesq_api_log_acesso ON md_pesq_api_log (dth_acesso);
CREATE INDEX idx_md_pesq_api_log_ip ON md_pesq_api_log (ip_usuario);
CREATE INDEX idx_md_pesq_api_log_codigo ON md_pesq_api_log (codigo_resposta);

-- Parâmetros de configuração da API
INSERT INTO md_pesq_parametro_pesquisa (id_parametro_pesquisa, nome, valor) VALUES 
(nextval('seq_md_pesq_parametro_pesquisa'), 'API_REST_HABILITADA', 'S');

INSERT INTO md_pesq_parametro_pesquisa (id_parametro_pesquisa, nome, valor) VALUES 
(nextval('seq_md_pesq_parametro_pesquisa'), 'API_REST_RATE_LIMIT', '1000');

INSERT INTO md_pesq_parametro_pesquisa (id_parametro_pesquisa, nome, valor) VALUES 
(nextval('seq_md_pesq_parametro_pesquisa'), 'API_REST_CORS_ORIGINS', '*');

INSERT INTO md_pesq_parametro_pesquisa (id_parametro_pesquisa, nome, valor) VALUES 
(nextval('seq_md_pesq_parametro_pesquisa'), 'API_REST_LOG_RETENCAO_DIAS', '90');

-- Comentários das tabelas
COMMENT ON TABLE md_pesq_api_key IS 'Tabela para gerenciamento de chaves de API REST do módulo Pesquisa Pública';
COMMENT ON COLUMN md_pesq_api_key.id_api_key IS 'Identificador único da chave de API';
COMMENT ON COLUMN md_pesq_api_key.id_contato IS 'Identificador do contato/usuário proprietário da chave';
COMMENT ON COLUMN md_pesq_api_key.api_key IS 'Chave de API criptografada';
COMMENT ON COLUMN md_pesq_api_key.sin_ativo IS 'Indica se a chave está ativa (S/N)';
COMMENT ON COLUMN md_pesq_api_key.dth_criacao IS 'Data/hora de criação da chave';
COMMENT ON COLUMN md_pesq_api_key.dth_ultimo_acesso IS 'Data/hora do último acesso com a chave';
COMMENT ON COLUMN md_pesq_api_key.dth_expiracao IS 'Data/hora de expiração da chave (opcional)';
COMMENT ON COLUMN md_pesq_api_key.descricao IS 'Descrição/nome da chave de API';
COMMENT ON COLUMN md_pesq_api_key.permissoes IS 'Permissões da chave em formato JSON';

COMMENT ON TABLE md_pesq_api_log IS 'Tabela para log de uso da API REST do módulo Pesquisa Pública';
COMMENT ON COLUMN md_pesq_api_log.id_log IS 'Identificador único do log';
COMMENT ON COLUMN md_pesq_api_log.id_api_key IS 'Identificador da chave de API utilizada (opcional)';
COMMENT ON COLUMN md_pesq_api_log.endpoint IS 'Endpoint acessado';
COMMENT ON COLUMN md_pesq_api_log.metodo IS 'Método HTTP utilizado';
COMMENT ON COLUMN md_pesq_api_log.parametros IS 'Parâmetros da requisição em formato JSON';
COMMENT ON COLUMN md_pesq_api_log.dth_acesso IS 'Data/hora do acesso';
COMMENT ON COLUMN md_pesq_api_log.ip_usuario IS 'Endereço IP do usuário';
COMMENT ON COLUMN md_pesq_api_log.user_agent IS 'User Agent do cliente';
COMMENT ON COLUMN md_pesq_api_log.codigo_resposta IS 'Código de resposta HTTP';
COMMENT ON COLUMN md_pesq_api_log.tempo_execucao IS 'Tempo de execução em milissegundos';