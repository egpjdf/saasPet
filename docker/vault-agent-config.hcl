# Vault Agent Configuration for Saaspet
# Runs as sidecar to inject secrets into application

pid_file = "/tmp/vault-agent.pid"

auto_auth {
  method "approle" {
    mount_path = "auth/approle"
    config = {
      role_id_file_path = "/etc/vault/role_id"
      secret_id_file_path = "/etc/vault/secret_id"
    }
  }

  sink "file" {
    config = {
      path = "/home/vault/.vault-token"
    }
  }
}

template {
  source      = "/etc/vault/templates/secrets.tpl"
  destination = "/home/vault/secrets/.env.vault"
  command     = "pkill -HUP -f 'php-fpm' || true"
}

template {
  source      = "/etc/vault/templates/tenant-secrets.tpl"
  destination = "/home/vault/secrets/tenant-secrets.json"
  command     = "pkill -HUP -f 'php-fpm' || true"
}

listener "tcp" {
  address = "0.0.0.0:8100"
  tls_disable = true
}

cache {
  use_auto_auth_token = true
}

vault {
  address = "https://vault.example.com"
  tls_skip_verify = false
}