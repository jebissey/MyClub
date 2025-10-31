export function generateClientId() {
    return `client_${crypto.randomUUID()}`;
}