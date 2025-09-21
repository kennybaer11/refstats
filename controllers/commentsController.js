const axios = require('axios');

exports.getCommentsPage = async (req, res) => {
  try {
    const response = await axios.get(`https://beta.kenyschulz.com/referee/api/getrefereecomments.php?ts=${Date.now()}`);
    const comments = response.data;
    res.render('admin-comments', { comments });
  } catch (err) {
    console.error('Error fetching comments:', err.message);
    res.status(500).send('Failed to load comments');
  }
};

// Approve a single comment
exports.approveComment = async (req, res) => {
  const id = req.params.id;
  try {
    await axios.post('https://beta.kenyschulz.com/referee/api/update-comment.php', new URLSearchParams({
      comment_id: id,
      approved: 1
    }));
    res.json({ success: true });
  } catch (err) {
    console.error('Error approving comment:', err.message);
    res.status(500).json({ success: false });
  }
};

// Delete a single comment
exports.deleteComment = async (req, res) => {
  const id = req.params.id;
  try {
    await axios.post('https://beta.kenyschulz.com/referee/api/delete-comment.php', new URLSearchParams({
      comment_id: id
    }));
    res.json({ success: true });
  } catch (err) {
    console.error('Error deleting comment:', err.message);
    res.status(500).json({ success: false });
  }
};

// Bulk action for comments
exports.bulkComments = async (req, res) => {
  const { ids, action, approved } = req.body;

  if (!ids || !Array.isArray(ids) || !ids.length) {
    return res.json({ success: false, error: 'No comment IDs provided' });
  }

  try {
    // Loop through IDs and perform the requested action
    for (const id of ids) {
      if (action === 'approve' || action === 'disapprove') {
        await axios.post('https://beta.kenyschulz.com/referee/api/update-comment.php', new URLSearchParams({
          comment_id: id,
          approved: approved
        }));
      } else if (action === 'delete') {
        await axios.post('https://beta.kenyschulz.com/referee/api/delete-comment.php', new URLSearchParams({
          comment_id: id
        }));
      }
    }

    res.json({ success: true });
  } catch (err) {
    console.error('Error performing bulk comment action:', err.message);
    res.status(500).json({ success: false, error: err.message });
  }
};
