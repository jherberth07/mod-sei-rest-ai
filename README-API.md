# API REST v1 - Módulo SEI Pesquisa Pública

Esta documentação descreve a API REST v1 do módulo Pesquisa Pública do SEI, que fornece acesso programático aos dados públicos de processos e documentos.

## Índice

- [Visão Geral](#visão-geral)
- [Autenticação](#autenticação)
- [Endpoints](#endpoints)
- [Exemplos de Uso](#exemplos-de-uso)
- [Códigos de Erro](#códigos-de-erro)
- [Instalação e Configuração](#instalação-e-configuração)
- [Limitações e Rate Limiting](#limitações-e-rate-limiting)

## Visão Geral

A API REST v1 permite:
- Pesquisar processos públicos
- Obter detalhes de processos específicos
- Listar documentos de processos
- Obter conteúdo de documentos públicos
- Pesquisar documentos por critérios específicos

### URL Base

```
http://[SEU_SERVIDOR]/sei/modulos/pesquisa/api/v1/
```

### Formato de Resposta

Todas as respostas são retornadas em formato JSON com a seguinte estrutura:

```json
{
  "success": true|false,
  "message": "Mensagem descritiva",
  "data": {...},
  "timestamp": "2024-01-01T10:00:00+00:00",
  "version": "v1"
}
```

Para erros:

```json
{
  "success": false,
  "error": {
    "code": "CODIGO_ERRO",
    "message": "Mensagem de erro",
    "details": "Detalhes adicionais (opcional)"
  },
  "timestamp": "2024-01-01T10:00:00+00:00",
  "version": "v1"
}
```

## Autenticação

A API utiliza autenticação por API Key. Existem duas formas de enviar a chave:

### Header X-API-Key
```
X-API-Key: sua_api_key_aqui
```

### Authorization Bearer
```
Authorization: Bearer sua_api_key_aqui
```

### Obtendo uma API Key

Para obter uma API key, use o endpoint de autenticação:

```http
POST /auth
Content-Type: application/json

{
  "username": "seu_usuario",
  "password": "sua_senha"
}
```

Resposta:
```json
{
  "success": true,
  "data": {
    "api_key": "sua_nova_api_key",
    "expires_in": 3600,
    "token_type": "Bearer",
    "user": {
      "username": "seu_usuario"
    }
  }
}
```

## Endpoints

### 1. Health Check

Verifica se a API está funcionando.

```http
GET /health
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "status": "ok",
    "service": "SEI Pesquisa Pública API",
    "version": "v1",
    "endpoints": {...}
  }
}
```

### 2. Autenticação

Obtém uma API key para acesso aos endpoints protegidos.

```http
POST /auth
```

**Parâmetros:**
- `username` (string): Nome de usuário
- `password` (string): Senha

### 3. Listar Processos

Lista processos públicos com paginação.

```http
GET /processes?limit=20&offset=0&orgao_id=1
```

**Parâmetros de consulta:**
- `limit` (int, opcional): Número máximo de resultados (padrão: 20, máximo: 100)
- `offset` (int, opcional): Número de registros para pular (padrão: 0)
- `orgao_id` (int, opcional): ID do órgão para filtrar

**Resposta:**
```json
{
  "success": true,
  "data": {
    "processes": [
      {
        "id": 123456,
        "protocol": "1234.567890/2024-11",
        "type": "Processo Administrativo",
        "opening_date": "2024-01-01 10:00:00",
        "status": "Aberto",
        "generating_unit": {
          "id": 1,
          "acronym": "UNIT",
          "description": "Unidade Geradora"
        },
        "organ": {
          "id": 1,
          "acronym": "ORG",
          "description": "Órgão"
        }
      }
    ],
    "total": 100,
    "limit": 20,
    "offset": 0,
    "returned": 20
  }
}
```

### 4. Obter Processo

Obtém detalhes de um processo específico.

```http
GET /processes/{id}
```

**Parâmetros:**
- `id` (int): ID do processo

**Resposta:**
```json
{
  "success": true,
  "data": {
    "id": 123456,
    "protocol": "1234.567890/2024-11",
    "type": "Processo Administrativo",
    "opening_date": "2024-01-01 10:00:00",
    "status": "Aberto",
    "specification": "Especificação do processo",
    "access_level": "Público",
    "global_access_level": "Público",
    "generating_unit": {...},
    "organ": {...}
  }
}
```

### 5. Listar Documentos do Processo

Lista documentos públicos de um processo.

```http
GET /processes/{id}/documents
```

**Parâmetros:**
- `id` (int): ID do processo

**Resposta:**
```json
{
  "success": true,
  "data": {
    "process_id": 123456,
    "documents": [
      {
        "id": 789012,
        "protocol": "1234.567890/2024-11-DOC001",
        "series": "Documento de Origem Externa",
        "number": "001",
        "generation_date": "2024-01-01 10:00:00",
        "status": "Gerado",
        "access_level": "Público"
      }
    ],
    "total": 5
  }
}
```

### 6. Pesquisar Processos

Pesquisa processos por termos específicos.

```http
GET /processes/search?q=termo_pesquisa&limit=20&offset=0
```

**Parâmetros de consulta:**
- `q` (string): Termo de pesquisa (mínimo 3 caracteres)
- `limit` (int, opcional): Número máximo de resultados
- `offset` (int, opcional): Número de registros para pular

### 7. Obter Documento

Obtém detalhes de um documento específico.

```http
GET /documents/{id}
```

**Parâmetros:**
- `id` (int): ID do documento

### 8. Obter Conteúdo do Documento

Obtém o conteúdo de um documento em diferentes formatos.

```http
GET /documents/{id}/content?format=html
```

**Parâmetros:**
- `id` (int): ID do documento
- `format` (string, opcional): Formato do conteúdo (html, text, pdf)

### 9. Pesquisar Documentos

Pesquisa documentos por critérios específicos.

```http
GET /documents/search?q=termo&series_type=tipo&date_from=2024-01-01&date_to=2024-12-31
```

**Parâmetros de consulta:**
- `q` (string): Termo de pesquisa (mínimo 3 caracteres)
- `series_type` (string, opcional): Tipo de série do documento
- `date_from` (string, opcional): Data inicial (YYYY-MM-DD)
- `date_to` (string, opcional): Data final (YYYY-MM-DD)
- `limit` (int, opcional): Número máximo de resultados
- `offset` (int, opcional): Número de registros para pular

## Exemplos de Uso

### Exemplo com cURL

```bash
# 1. Obter API key
curl -X POST http://seu-servidor/sei/modulos/pesquisa/api/v1/auth \
  -H "Content-Type: application/json" \
  -d '{"username":"usuario","password":"senha"}'

# 2. Listar processos
curl -X GET http://seu-servidor/sei/modulos/pesquisa/api/v1/processes \
  -H "X-API-Key: sua_api_key"

# 3. Pesquisar processos
curl -X GET "http://seu-servidor/sei/modulos/pesquisa/api/v1/processes/search?q=licitacao" \
  -H "X-API-Key: sua_api_key"
```

### Exemplo com PHP

```php
<?php
// Classe simples para usar a API
class SeiPesquisaApi {
    private $baseUrl;
    private $apiKey;
    
    public function __construct($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function authenticate($username, $password) {
        $data = json_encode(['username' => $username, 'password' => $password]);
        $response = $this->request('POST', '/auth', $data);
        
        if ($response['success']) {
            $this->apiKey = $response['data']['api_key'];
            return true;
        }
        return false;
    }
    
    public function getProcesses($limit = 20, $offset = 0) {
        return $this->request('GET', "/processes?limit={$limit}&offset={$offset}");
    }
    
    public function searchProcesses($query, $limit = 20) {
        $query = urlencode($query);
        return $this->request('GET', "/processes/search?q={$query}&limit={$limit}");
    }
    
    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        $headers = ['Content-Type: application/json'];
        
        if ($this->apiKey) {
            $headers[] = 'X-API-Key: ' . $this->apiKey;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

// Uso
$api = new SeiPesquisaApi('http://seu-servidor/sei/modulos/pesquisa/api/v1');
$api->authenticate('usuario', 'senha');
$processos = $api->getProcesses();
?>
```

### Exemplo com Python

```python
import requests
import json

class SeiPesquisaApi:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.api_key = None
        self.session = requests.Session()
    
    def authenticate(self, username, password):
        response = self.session.post(
            f"{self.base_url}/auth",
            json={"username": username, "password": password}
        )
        
        if response.json().get('success'):
            self.api_key = response.json()['data']['api_key']
            self.session.headers.update({'X-API-Key': self.api_key})
            return True
        return False
    
    def get_processes(self, limit=20, offset=0):
        response = self.session.get(
            f"{self.base_url}/processes",
            params={"limit": limit, "offset": offset}
        )
        return response.json()
    
    def search_processes(self, query, limit=20):
        response = self.session.get(
            f"{self.base_url}/processes/search",
            params={"q": query, "limit": limit}
        )
        return response.json()

# Uso
api = SeiPesquisaApi('http://seu-servidor/sei/modulos/pesquisa/api/v1')
api.authenticate('usuario', 'senha')
processos = api.get_processes()
```

## Códigos de Erro

| Código | Descrição |
|--------|-----------|
| `MISSING_API_KEY` | API key não fornecida |
| `INVALID_API_KEY` | API key inválida ou expirada |
| `MISSING_PARAMETERS` | Parâmetros obrigatórios não informados |
| `INVALID_JSON` | JSON inválido no corpo da requisição |
| `ENDPOINT_NOT_FOUND` | Endpoint não encontrado |
| `METHOD_NOT_ALLOWED` | Método HTTP não permitido |
| `PROCESS_NOT_FOUND` | Processo não encontrado |
| `DOCUMENT_NOT_FOUND` | Documento não encontrado |
| `ACCESS_DENIED` | Acesso negado ao recurso |
| `SEARCH_TERM_TOO_SHORT` | Termo de pesquisa muito curto |
| `INVALID_FORMAT` | Formato de arquivo não suportado |
| `RATE_LIMIT_EXCEEDED` | Limite de requisições excedido |
| `INTERNAL_ERROR` | Erro interno do servidor |

## Instalação e Configuração

### Pré-requisitos

- SEI versão 4.0.12 ou superior
- Módulo Pesquisa Pública versão 4.3.0 ou superior
- PHP 7.0 ou superior

### Instalação

1. **Atualizar o módulo Pesquisa Pública para a versão 4.4.0**
2. **Executar o script de migração**:
   ```bash
   php /opt/sei/scripts/sei_atualizar_versao_modulo_pesquisa.php
   ```

### Configuração

A API pode ser configurada através dos parâmetros do módulo:

- `API_REST_HABILITADA`: Habilita/desabilita a API (S/N)
- `API_REST_RATE_LIMIT`: Limite de requisições por hora (padrão: 1000)
- `API_REST_CORS_ORIGINS`: Origins permitidas para CORS (padrão: *)
- `API_REST_LOG_RETENCAO_DIAS`: Dias de retenção dos logs (padrão: 90)

### URL de Acesso

A API estará disponível em:
```
http://[SEU_SERVIDOR]/sei/modulos/pesquisa/api/v1/
```

### Configuração do Servidor Web

#### Apache (.htaccess)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/v1/(.*)$ api/v1/index.php [QSA,L]
```

#### Nginx

```nginx
location /sei/modulos/pesquisa/api/v1/ {
    try_files $uri $uri/ /sei/modulos/pesquisa/api/v1/index.php?$args;
}
```

## Limitações e Rate Limiting

- **Rate Limiting**: Por padrão, 1000 requisições por hora por API key
- **Paginação**: Máximo de 100 registros por página
- **Termo de pesquisa**: Mínimo de 3 caracteres
- **Acesso**: Apenas a dados públicos
- **Formatos de documento**: HTML, texto e PDF (quando disponível)

## Logs e Monitoramento

A API registra automaticamente:
- Todas as requisições e respostas
- Erros e exceções
- Tempo de execução
- IP e User-Agent dos clientes

Os logs são armazenados na tabela `md_pesq_api_log` e podem ser consultados através da administração do módulo.

## Suporte

Para suporte técnico:
1. [Abrir Issue](https://github.com/jherberth07/mod-sei-rest-ai/issues) no repositório do GitHub
2. Consultar a documentação do módulo Pesquisa Pública
3. Verificar os logs da API para troubleshooting

## Versionamento

Esta é a versão 1.0 da API. Futuras versões manterão compatibilidade com a v1 sempre que possível. Mudanças breaking serão implementadas em versões maiores (v2, v3, etc.).