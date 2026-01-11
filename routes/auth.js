const express = require('express');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const User = require('../models/User');
const Transaction = require('../models/Transaction');

const router = express.Router();
const JWT_SECRET = process.env.JWT_SECRET || 'change-me';
const BCRYPT_ROUNDS = Number.parseInt(process.env.BCRYPT_ROUNDS || '10', 10);
const DEFAULT_BALANCE = Number.parseFloat(process.env.DEFAULT_BALANCE || '1000');

function buildToken(user) {
  return jwt.sign({ id: user._id.toString(), email: user.email, username: user.username }, JWT_SECRET, {
    expiresIn: '7d',
  });
}

router.post('/register', async (req, res) => {
  const { email, username, password } = req.body || {};
  if (!email || !username || !password) {
    return res.status(400).json({ error: 'Email, pseudo et mot de passe requis.' });
  }
  try {
    const existing = await User.findOne({ $or: [{ email }, { username }] });
    if (existing) {
      return res.status(409).json({ error: 'Compte déjà existant.' });
    }
    const passwordHash = await bcrypt.hash(password, BCRYPT_ROUNDS);
    const user = await User.create({ email, username, passwordHash, balance: DEFAULT_BALANCE });
    await Transaction.create({
      userId: user._id,
      amount: DEFAULT_BALANCE,
      type: 'credit',
      description: 'Solde initial fictif',
    });
    const token = buildToken(user);
    return res.status(201).json({ token, user: { id: user._id, email, username, balance: user.balance } });
  } catch (error) {
    console.error('register error', error);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

router.post('/login', async (req, res) => {
  const { identifier, password } = req.body || {};
  if (!identifier || !password) {
    return res.status(400).json({ error: 'Identifiant et mot de passe requis.' });
  }
  try {
    const user = await User.findOne({ $or: [{ email: identifier }, { username: identifier }] });
    if (!user) {
      return res.status(401).json({ error: 'Identifiants invalides.' });
    }
    const match = await bcrypt.compare(password, user.passwordHash);
    if (!match) {
      return res.status(401).json({ error: 'Identifiants invalides.' });
    }
    const token = buildToken(user);
    return res.json({ token, user: { id: user._id, email: user.email, username: user.username, balance: user.balance } });
  } catch (error) {
    console.error('login error', error);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

router.post('/logout', (req, res) => {
  res.json({ ok: true });
});

module.exports = router;
