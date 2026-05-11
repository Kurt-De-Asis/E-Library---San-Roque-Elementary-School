// Change this to your server's URL
// For local WAMP: http://localhost/e-library/web
// For LAN testing: http://192.168.x.x/e-library/web
// For production: https://your-domain.com/web
const BASE_URL = 'http://localhost/e-library/web';

export const API_BASE_URL = `${BASE_URL}/api`;
export const UPLOADS_URL = `${BASE_URL}/uploads`;
export const COVERS_URL = `${UPLOADS_URL}/covers`;
export const BOOKS_URL = `${UPLOADS_URL}/books`;

export default API_BASE_URL;
