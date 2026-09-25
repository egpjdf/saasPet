<?php

declare(strict_types=1);

namespace App\Services\LGPD;

use App\Models\Organization;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DpaGenerator
{
    public function generate(Organization $organization): string
    {
        $dpa = $this->buildDpaContent($organization);
        $filename = "DPA_{$organization->slug}_" . now()->format('Ymd_His') . ".pdf";

        // Save as text file (would be PDF in production)
        $path = "dpa/{$filename}";
        Storage::disk('local')->put($path, $dpa);

        return storage_path("app/{$path}");
    }

    public function getDpaContent(Organization $organization): string
    {
        return $this->buildDpaContent($organization);
    }

    private function buildDpaContent(Organization $organization): string
    {
        $date = now()->format('d/m/Y');
        $dpaVersion = '1.0';
        $controllerName = $organization->name;
        $controllerAddress = $organization->settings['address'] ?? 'Endereço não informado';
        $processorName = 'Saaspet Plataforma Ltda.';
        $processorAddress = 'São Paulo, SP, Brasil';

        return <<<DPA
DATA PROCESSING AGREEMENT (DPA)
Versão: {$dpaVersion}
Data: {$date}

ENTRE:

1. CONTROLADOR DE DADOS:
   {$controllerName}
   Endereço: {$controllerAddress}
   CNPJ: {$organization->settings['cnpj'] ?? 'Não informado'}
   E-mail DPO: {$organization->settings['dpo_email'] ?? 'dpo@' . $organization->slug . '.com.br'}

2. OPERADOR DE DADOS:
   {$processorName}
   Endereço: {$processorAddress}
   CNPJ: 00.000.000/0001-00
   E-mail DPO: dpo@saaspet.com.br

CLÁUSULAS:

1. OBJETO E ESCOPO
1.1 Este Adendo de Processamento de Dados ("DPA") regula o tratamento de dados pessoais realizado pelo Operador em nome do Controlador, no contexto da prestação dos serviços da plataforma Saaspet.
1.2 O Operador tratará apenas os dados pessoais necessários para a execução dos serviços contratados, conforme descrito no Contrato Principal.

2. CATEGORIAS DE DADOS PESSOAIS
O Operador poderá tratar as seguintes categorias de dados pessoais:
- Dados de identificação (nome, e-mail, CPF, telefone)
- Dados de contato profissional
- Dados de autenticação e segurança (credenciais, 2FA, logs de acesso)
- Dados de faturamento e cobrança
- Dados de uso da plataforma (logs de auditoria, preferências)
- Dados de comunicação (notificações, mensagens)

3. FINALIDADES DO TRATAMENTO
- Prestação dos serviços da plataforma Saaspet
- Gestão de usuários, organizações e workspaces
- Processamento de pagamentos e faturamento
- Envio de notificações transacionais
- Cumprimento de obrigações legais e regulatórias
- Segurança e prevenção de fraudes
- Suporte técnico e atendimento ao cliente

4. BASES LEGAIS
O tratamento baseia-se nas seguintes hipóteses legais (LGPD Art. 7º):
- Execução de contrato (Art. 7º, V)
- Cumprimento de obrigação legal (Art. 7º, II)
- Exercício regular de direitos (Art. 7º, VI)
- Legítimo interesse (Art. 7º, IX) - para segurança e melhoria dos serviços
- Consentimento (Art. 7º, I) - quando aplicável (marketing, cookies não essenciais)

5. SUBOPERADORES
O Controlador autoriza genericamente a subcontratação dos seguintes suboperadores:
5.1 Infraestrutura e Hospedagem:
   - Amazon Web Services (AWS) / Cloudflare
   - PostgreSQL / Redis gerenciados
5.2 Processamento de Pagamentos:
   - Stripe Inc. (EUA) - Cláusulas Contratuais Padrão (SCC)
   - Paddle.com Market Ltd. (Reino Unido) - SCC
5.3 Comunicações:
   - Resend (EUA) - SCC
   - Twilio/Vonage (SMS) - SCC
5.4 Observabilidade:
   - Sentry (EUA) - SCC
5.5 IA/ML:
   - OpenAI / Anthropic (EUA) - SCC

Lista completa de suboperadores disponível em: https://saaspet.com/subprocessors
O Controlador será notificado com 30 dias de antecedência sobre novos suboperadores.

6. TRANSFERÊNCIA INTERNACIONAL
6.1 Alguns suboperadores estão localizados fora do Brasil.
6.2 As transferências baseiam-se em:
   - Cláusulas Contratuais Padrão (SCC) da Comissão Europeia
   - Decisões de adequação (quando aplicável)
   - Regras Corporativas Vinculantes (BCR) - quando disponíveis
6.3 Cópias das SCC estão disponíveis mediante solicitação.

7. DIREITOS DOS TITULARES (LGPD Art. 18)
O Operador auxiliará o Controlador no atendimento aos direitos dos titulares:
- Confirmação da existência de tratamento
- Acesso aos dados
- Correção de dados incompletos, inexatos ou desatualizados
- Anonimização, bloqueio ou eliminação de dados desnecessários
- Portabilidade dos dados
- Eliminação dos dados tratados com consentimento
- Informação sobre compartilhamento
- Informação sobre possibilidade de não consentir
- Revogação do consentimento
- Oposição ao tratamento

Prazo de resposta: 15 dias (conforme LGPD).

8. MEDIDAS DE SEGURANÇA (LGPD Art. 46, 48, 50)
O Operador implementa:
- Criptografia em trânsito (TLS 1.3) e em repouso (AES-256)
- Controle de acesso baseado em funções (RBAC) + Multi-tenancy com RLS
- Autenticação multifator (2FA) obrigatória para administradores
- Logs de auditoria imutáveis
- Testes de penetração anuais
- Programa de bug bounty
- Certificação ISO 27001 (em andamento)
- Plano de resposta a incidentes

9. NOTIFICAÇÃO DE INCIDENTES (LGPD Art. 48)
9.1 O Operador notificará o Controlador em até 24 horas após tomar conhecimento de incidente de segurança que possa acarretar risco ou dano relevante aos titulares.
9.2 A notificação conterá:
   - Natureza dos dados afetados
   - Titulares envolvidos
   - Medidas técnicas e de segurança utilizadas
   - Riscos relacionados
   - Medidas adotadas para reverter/mitigar
9.3 O Operador cooperará com a ANPD conforme necessário.

10. RETENÇÃO E ELIMINAÇÃO
10.1 Dados serão mantidos pelo tempo necessário para cumprir as finalidades.
10.2 Prazos de retenção padrão:
   - Dados de cadastro: Durante a vigência do contrato + 5 anos
   - Logs de auditoria: 7 anos (anonimizados após 2 anos)
   - Dados de faturamento: 7 anos (obrigação fiscal)
   - Notificações: 1 ano
   - Cookies/Preferências: 13 meses
10.3 Após término do contrato: eliminação em 30 dias ou anonimização.

11. AUDITORIA E INSPEÇÃO
11.1 O Controlador poderá auditar o cumprimento deste DPA mediante aviso prévio de 30 dias.
11.2 O Operador fornece relatórios SOC 2 Type II anualmente.
11.3 Auditorias on-site limitadas a 1 por ano, custo compartilhado.

12. VIGÊNCIA E RESCISÃO
12.1 Este DPA vigora enquanto durar o Contrato Principal.
12.2 Na rescisão: eliminação ou devolução dos dados em 30 dias.
12.3 Cláusulas de confidencialidade e segurança sobrevivem por 5 anos.

13. LEI APLICÁVEL E FORO
Lei brasileira. Foro da Comarca de São Paulo/SP.

---
Assinatura Digital:
Controlador: _________________________ Data: ___________
Operador: ___________________________ Data: ___________

Versão: {$dpaVersion} | Hash: {$this->generateHash($organization, $dpaVersion)}
DPA;
    }

    private function generateHash(Organization $organization, string $version): string
    {
        return substr(hash('sha256', $organization->id . $version . now()->format('Ymd')), 0, 16);
    }
}