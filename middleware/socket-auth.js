const jwt = require('jsonwebtoken');

const JWT_SECRET = process.env.JWT_SECRET || 'change-me';

function ensureAuthenticatedSocket(socket, next) {
  const token = socket.handshake.auth?.token || socket.handshake.query?.token;
  if (!token) {
    return next(new Error('Authentication required'));
  }
  try {
    const payload = jwt.verify(token, JWT_SECRET);
    socket.user = payload;
    socket.join(`user:${payload.id}`);
    return next();
  } catch (error) {
    return next(new Error('Invalid token'));
  }
}

module.exports = { ensureAuthenticatedSocket };
