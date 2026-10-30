/* CampusVoice data-access helpers. These are intentionally small and RLS-aware. */
window.CampusVoiceAPI = {
  async getActiveCategories() {
    const { data, error } = await CampusVoiceBackend.client
      .from('categories').select('category_id,category_name').eq('status','active').order('category_name');
    if (error) throw error;
    return data || [];
  },

  async getActiveLocations() {
    const { data, error } = await CampusVoiceBackend.client
      .from('locations').select('location_id,building,floor,area').eq('status','active').order('building').order('floor').order('area');
    if (error) throw error;
    return data || [];
  },

  async getDefaultPriority() {
    const { data, error } = await CampusVoiceBackend.client
      .from('priorities').select('priority_id,priority_name').eq('status','active').eq('priority_name','Medium').single();
    if (error) throw error;
    return data;
  },

  async getStatus(name) {
    const { data, error } = await CampusVoiceBackend.client
      .from('statuses').select('status_id,status_name').eq('status_name',name).single();
    if (error) throw error;
    return data;
  },

  async submitConcern({ title, description, categoryId, locationId, priorityId, studentId, file }) {
    const sb = CampusVoiceBackend.client;
    const submittedStatus = await this.getStatus('Submitted');

    const { data: concern, error: concernError } = await sb.from('concerns').insert({
      student_id: studentId,
      category_id: categoryId,
      location_id: locationId,
      priority_id: priorityId,
      status_id: submittedStatus.status_id,
      title,
      description
    }).select('concern_id,concern_code').single();
    if (concernError) throw concernError;

    if (file) {
      const safeName = file.name.replace(/[^a-zA-Z0-9._-]/g, '_');
      const path = `${studentId}/concerns/${concern.concern_id}/${Date.now()}-${safeName}`;
      const { error: uploadError } = await sb.storage.from('campusvoice-files').upload(path, file, { upsert:false });
      if (uploadError) throw uploadError;

      const { error: photoError } = await sb.from('photos').insert({
        concern_id: concern.concern_id,
        uploaded_by: studentId,
        photo_type: 'supporting',
        file_path: path
      });
      if (photoError) throw photoError;
    }

    const { error: updateError } = await sb.from('concern_updates').insert({
      concern_id: concern.concern_id,
      user_id: studentId,
      status_id: submittedStatus.status_id,
      update_type: 'Concern Submitted',
      response: 'Your concern has been received and is currently awaiting review.'
    });
    if (updateError) throw updateError;

    return concern;
  }
};
