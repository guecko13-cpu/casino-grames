const express = require('express');
const User = require('../models/User');
const { authMiddleware } = require('../middleware/auth');

const router = express.Router();

router.get('/me', authMiddleware, async (req, res) => {
  try {
    const user = await User.findById(req.user.id).lean();
    if (!user) {
      return res.status(404).json({ error: 'Utilisateur introuvable.' });
    }
    return res.json({ id: user._id, email: user.email, username: user.username, balance: user.balance });
  } catch (error) {
    console.error('user me error', error);
    return res.status(500).json({ error: 'Erreur serveur.' });
  }
});

module.exports = router;
