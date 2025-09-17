// quill editor initializing
const quill = new Quill('#editor', {
  theme: 'snow',
  placeholder: 'Write your news here...',
  modules: {
    toolbar: [
      ['bold', 'italic', 'underline'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['link']
    ]
  }
});


const newsForm = document.getElementById('add-news');
const status = document.getElementById('news-status');

newsForm.addEventListener('submit', async (e) => {
  e.preventDefault();

  // Copy Quill content into hidden textarea
  const contentTextarea = document.getElementById('content');
  contentTextarea.value = quill.root.innerHTML;

  const formData = new FormData(newsForm);
  const payload = {
    title: formData.get('title'),
    content: formData.get('content') // now includes Quill HTML
  };

  try {
    const res = await fetch('/admin/add-news', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await res.json();

    if (result.success) {
      status.innerText = `✅ News posted with ID ${result.id}`;
      newsForm.reset();
      quill.setContents([]); // clears editor
    } else {
      status.innerText = `❌ Error: ${result.error}`;
    }
  } catch (err) {
    status.innerText = `❌ Network error: ${err.message}`;
  }
});