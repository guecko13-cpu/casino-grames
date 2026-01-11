const path = require('path');

const express = require('express');
const http = require('http');
const mongoose = require('mongoose');
const dotenv = require('dotenv');
const { Server } = require('socket.io');

const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/user');
const walletRoutes = require('./routes/wallet');
const { buildVersion } = require('./utils/version');
const { ensureAuthenticatedSocket } = require('./middleware/socket-auth');

dotenv.config();

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  path: '/socket.io',
  cors: {
    origin: false,
  },
});

const PORT = Number.parseInt(process.env.PORT || '3000', 10);
const MONGO_URI = process.env.MONGO_URI || 'mongodb://127.0.0.1:27017/casino_games_fun';

mongoose.set('strictQuery', true);

mongoose
  .connect(MONGO_URI)
  .then(() => {
    console.log('MongoDB connected');
  })
  .catch((err) => {
    console.error('MongoDB connection error', err);
  });

io.use(ensureAuthenticatedSocket);

io.on('connection', (socket) => {
  socket.emit('connection:ready', { message: 'Socket connected' });
});

app.set('trust proxy', true);
app.use(express.json({ limit: '1mb' }));

app.get('/api/health', (req, res) => {
  const state = mongoose.connection.readyState;
  const mongoStatus = state === 1 ? 'connected' : state === 2 ? 'connecting' : 'disconnected';
  res.json({ status: 'ok', mongo: mongoStatus, time: new Date().toISOString() });
});

app.get('/api/version', (req, res) => {
  res.json({ version: buildVersion() });
});

app.use('/api/auth', authRoutes);
app.use('/api/user', userRoutes);
app.use('/api/wallet', walletRoutes(io));

const frontendDir = path.join(__dirname, 'frontend', 'dist');
const adminDir = path.join(__dirname, 'admin', 'dist');

app.use(express.static(frontendDir));
app.use('/admin', express.static(adminDir));

app.get('/admin/*', (req, res) => {
  res.sendFile(path.join(adminDir, 'index.html'));
});

app.get('*', (req, res) => {
  res.sendFile(path.join(frontendDir, 'index.html'));
});

server.listen(PORT, () => {
  console.log(`Server listening on port ${PORT}`);
});
