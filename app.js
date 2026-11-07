$(function () {
  const SUPABASE_URL = 'https://tttgwbzflzgmpthlavgb.supabase.co';
  // Esta é a chave pública (publishable), feita para uso no navegador.
  const SUPABASE_PUBLISHABLE_KEY = 'sb_publishable_jj1Yt5N3B9XcPqfVz0EOhQ_JOz9Ekv7';
  const $fields = { id: $('#id'), name: $('#name'), wish: $('#wish'), password: $('#password') }; let wishes = [];
  const show = message => $('#feedback').text(message || '');
  async function rpc(functionName, parameters) {
    const response = await fetch(`${SUPABASE_URL}/rest/v1/rpc/${functionName}`, {
      method: 'POST',
      headers: {
        apikey: SUPABASE_PUBLISHABLE_KEY,
        Authorization: `Bearer ${SUPABASE_PUBLISHABLE_KEY}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(parameters || {})
    });
    if (response.ok) return response.status === 204 ? null : response.json();
    const error = await response.json().catch(() => ({}));
    throw new Error(error.message || error.error || 'Não foi possível concluir a operação.');
  }
  function reset() { $('#wish-form')[0].reset(); $fields.id.val(''); $fields.name.prop('disabled', false); $('#form-title').text('Adicionar minha sugestão'); $('#form-hint').text('Escolha uma senha para poder alterar sua sugestão depois.'); $('#cancel').prop('hidden', true); }
  function openForm() { $('#form-card').prop('hidden', false); $('#open-form').prop('hidden', true); $fields.name.trigger('focus'); }
  function closeForm() { reset(); $('#form-card').prop('hidden', true); $('#open-form').prop('hidden', false); }
  function render() { const $list = $('#list').empty(); if (!wishes.length) $list.append($('<p>', { class: 'empty', text: 'Ainda ninguém adicionou uma sugestão.' })); wishes.forEach(item => { const $card = $('<article>', { class: 'wish' }); const $text = $('<div>').append($('<strong>').text(item.name), $('<p>').text(item.wish)); const $edit = $('<button>', { class: 'edit', text: 'Editar' }).on('click', () => { $fields.id.val(item.id); $fields.name.val(item.name).prop('disabled', true); $fields.wish.val(item.wish); $fields.password.val('').attr('autocomplete', 'current-password'); $('#form-title').text(`Editar sugestão de ${item.name}`); $('#form-hint').text('Confirme sua senha para salvar a alteração.'); $('#cancel').prop('hidden', false); $('#form-card').prop('hidden', false); $('#open-form').prop('hidden', true); $fields.wish.trigger('focus'); }); $card.append($text, $edit); $list.append($card); }); }
  function load() { return rpc('list_wishes').then(data => { wishes = data; render(); }).catch(() => show('Não foi possível carregar a lista.')); }
  $('#wish-form').on('submit', async function (event) {
    event.preventDefault(); show('');
    const editing = Boolean($fields.id.val());
    try {
      if (editing) await rpc('update_wish', { p_id: $fields.id.val(), p_wish: $fields.wish.val(), p_password: $fields.password.val() });
      else await rpc('add_wish', { p_name: $fields.name.val(), p_wish: $fields.wish.val(), p_password: $fields.password.val() });
      closeForm(); show('Sugestão salva!'); load();
    } catch (error) { show(error.message); }
  });
  $('#open-form').on('click', openForm); $('#cancel').on('click', closeForm); load();
});
