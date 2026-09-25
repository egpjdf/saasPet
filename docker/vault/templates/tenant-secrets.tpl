{
  "tenants": {
    {{- range $org := secret "secret/saaspet/tenants/organizations" }}
    "{{ $org.Key }}": {
      "id": "{{ $org.Key }}",
      "name": "{{ $org.Value.name }}",
      "slug": "{{ $org.Value.slug }}",
      "status": "{{ $org.Value.status }}",
      "settings": {{ $org.Value.settings | toJson }},
      "workspaces": {
        {{- range $ws := secret (printf "secret/saaspet/tenants/%s/workspaces" $org.Key) }}
        "{{ $ws.Key }}": {
          "id": "{{ $ws.Key }}",
          "name": "{{ $ws.Value.name }}",
          "slug": "{{ $ws.Value.slug }}",
          "status": "{{ $ws.Value.status }}",
          "settings": {{ $ws.Value.settings | toJson }}
        }{{- if not (last $ws) }},{{ end }}
        {{- end }}
      }
    }{{- if not (last $org) }},{{ end }}
    {{- end }}
  },
  "generated_at": "{{ now | date \"2006-01-02T15:04:05Z\" }}"
}