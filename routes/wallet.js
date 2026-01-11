const express = require('express');
const User = require('../models/User');
const Transaction = require('../models/Transaction');
const { authMiddleware } = require('../middleware/auth');

function walletRoutes(io) {
  const router = express.Router();

  async function appendTransaction({ userId, amount, type, description }) {
    return Transaction.create({ userId, amount, type, description });
  }

  router.get('/', authMiddleware, async (req, res) => {
    try {
      const user = await User.findById(req.user.id).lean();
      if (!user) {
        return res.status(404).json({ error: 'Utilisateur introuvable.' });
      }
      const transactions = await Transaction.find({ userId: user._id })
        .sort({ createdAt: -1 })
        .limit(20)
        .lean();
      return res.json({ balance: user.balance, transactions });
    } catch (error) {
      console.error('wallet info error', error);
      return res.status(500).json({ error: 'Erreur serveur.' });
    }
  });

  router.get('/history', authMiddleware, async (req, res) => {
    try {
      const transactions = await Transaction.find({ userId: req.user.id }).sort({ createdAt: -1 }).lean();
      return res.json({ transactions });
    } catch (error) {
      console.error('wallet history error', error);
      return res.status(500).json({ error: 'Erreur serveur.' });
    }
  });

  router.post('/credit', authMiddleware, async (req, res) => {
    const { amount, description } = req.body || {};
    const numericAmount = Number.parseFloat(amount);
    if (!Number.isFinite(numericAmount) || numericAmount <= 0) {
      return res.status(400).json({ error: 'Montant invalide.' });
    }
    try {
      const user = await User.findById(req.user.id);
      if (!user) {
        return res.status(404).json({ error: 'Utilisateur introuvable.' });
      }
      user.balance += numericAmount;
      await user.save();
      await appendTransaction({
        userId: user._id,
        amount: numericAmount,
        type: 'credit',
        description: description || 'Crédit fictif',
      });
      io.to(`user:${user._id}`).emit('balance:update', { balance: user.balance });
      return res.json({ balance: user.balance });
    } catch (error) {
      console.error('wallet credit error', error);
      return res.status(500).json({ error: 'Erreur serveur.' });
    }
  });

  router.post('/debit', authMiddleware, async (req, res) => {
    const { amount, description } = req.body || {};
    const numericAmount = Number.parseFloat(amount);
    if (!Number.isFinite(numericAmount) || numericAmount <= 0) {
      return res.status(400).json({ error: 'Montant invalide.' });
    }
    try {
      const user = await User.findById(req.user.id);
      if (!user) {
        return res.status(404).json({ error: 'Utilisateur introuvable.' });
      }
      if (user.balance < numericAmount) {
        return res.status(400).json({ error: 'Solde insuffisant.' });
      }
      user.balance -= numericAmount;
      await user.save();
      await appendTransaction({
        userId: user._id,
        amount: numericAmount,
        type: 'debit',
        description: description || 'Débit fictif',
      });
      io.to(`user:${user._id}`).emit('balance:update', { balance: user.balance });
      return res.json({ balance: user.balance });
    } catch (error) {
      console.error('wallet debit error', error);
      return res.status(500).json({ error: 'Erreur serveur.' });
    }
  });

  return router;
}

module.exports = walletRoutes;
