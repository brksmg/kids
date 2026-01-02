/**
 * Veo 3 Kids Prompt Generator - Logic
 * Features: API Calls, Copy, Export, Presets, Language Toggle
 */

document.addEventListener('DOMContentLoaded', () => {
    // State
    let currentLanguage = 'id';
    let currentData = null;

    // Elements
    const generateBtn = document.getElementById('generateBtn');
    const loading = document.getElementById('loading');
    const resultsArea = document.getElementById('results');
    const sceneContainer = document.getElementById('sceneContainer');
    const langToggle = document.getElementById('langToggle');
    const toast = document.getElementById('toast');

    // Language Toggle
    langToggle.addEventListener('click', () => {
        currentLanguage = currentLanguage === 'id' ? 'en' : 'id';
        langToggle.innerText = currentLanguage === 'id' ? '🇮🇩 ID / 🇬🇧 EN' : '🇬🇧 EN / 🇮🇩 ID';
        showToast(`Language switched to ${currentLanguage === 'id' ? 'Indonesian' : 'English'}`);
    });

    // Generate Logic
    generateBtn.addEventListener('click', async () => {
        const profession = document.getElementById('profession').value;
        if (!profession) return showToast('Please enter a profession!');

        const params = {
            profession,
            duration: document.getElementById('duration').value,
            scenes: document.getElementById('scenes').value,
            style: document.getElementById('style').value,
            age: document.getElementById('age').value,
            intensity: document.getElementById('intensity').value,
            narration: document.getElementById('narration').value,
            music: document.getElementById('music').value,
            lang: currentLanguage
        };

        loading.classList.remove('hidden');
        resultsArea.classList.add('hidden');

        try {
            const result = await fetchWithRetry('index.php?action=generate', params);
            if (result.error) throw new Error(result.error);

            // Clean data from Gemini
            const parsed = result.candidates?.[0]?.content?.parts?.[0]?.text;
            if (!parsed) throw new Error('Failed to parse AI response');
            
            const data = JSON.parse(parsed);
            currentData = data;
            renderScenes(data.scenes);
            
            loading.classList.add('hidden');
            resultsArea.classList.remove('hidden');
            resultsArea.scrollIntoView({ behavior: 'smooth' });
        } catch (err) {
            loading.classList.add('hidden');
            alert(err.message);
        }
    });

    // API Wrapper with Backoff
    async function fetchWithRetry(url, body, retries = 5, delay = 1000) {
        for (let i = 0; i < retries; i++) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await response.json();
                if (response.ok) return data;
                
                // If 429 (Rate Limit), wait and retry
                if (response.status !== 429) throw new Error(data.error || 'Request failed');
            } catch (err) {
                if (i === retries - 1) throw err;
            }
            await new Promise(r => setTimeout(r, delay));
            delay *= 2; // Exponential
        }
    }

    // UI Rendering
    function renderScenes(scenes) {
        sceneContainer.innerHTML = '';
        scenes.forEach((scene, index) => {
            const card = document.createElement('div');
            card.className = 'scene-card';
            card.innerHTML = `
                <h3>Scene ${index + 1}: ${scene.title} 
                    <button class="btn-text" onclick="copyText('scene-v-${index}')">Copy Scene</button>
                </h3>
                <div class="scene-meta">
                    <div><strong>Camera:</strong> ${scene.camera}</div>
                    <div><strong>Action:</strong> ${scene.action}</div>
                    <div><strong>Mood:</strong> ${scene.mood}</div>
                </div>
                <div class="prompt-fields">
                    <div class="field">
                        <label>VEO 3 VIDEO PROMPT</label>
                        <textarea class="prompt-box" id="scene-v-${index}" readonly>${scene.video_prompt}</textarea>
                    </div>
                    <div class="field">
                        <label>AUDIO / NARRATION PROMPT</label>
                        <textarea class="prompt-box" id="scene-a-${index}" readonly>${scene.audio}</textarea>
                    </div>
                </div>
            `;
            sceneContainer.appendChild(card);
        });
    }

    // Global Utilities
    window.copyText = (id) => {
        const text = document.getElementById(id).value;
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showToast('Copied to clipboard!');
    };

    document.getElementById('copyAll').addEventListener('click', () => {
        if (!currentData) return;
        const allText = currentData.scenes.map((s, i) => 
            `SCENE ${i+1}: ${s.title}\nVIDEO: ${s.video_prompt}\nAUDIO: ${s.audio}\n`
        ).join('\n---\n');
        
        const temp = document.createElement('textarea');
        temp.value = allText;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showToast('All scenes copied!');
    });

    // Exports
    document.getElementById('exportTxt').addEventListener('click', () => {
        if (!currentData) return;
        const blob = new Blob([JSON.stringify(currentData, null, 2)], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `veo-prompts-${Date.now()}.txt`;
        a.click();
    });

    document.getElementById('exportJson').addEventListener('click', () => {
        if (!currentData) return;
        const blob = new Blob([JSON.stringify(currentData)], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `veo-data-${Date.now()}.json`;
        a.click();
    });

    // Presets
    document.getElementById('savePreset').addEventListener('click', () => {
        const settings = {
            profession: document.getElementById('profession').value,
            duration: document.getElementById('duration').value,
            scenes: document.getElementById('scenes').value,
            style: document.getElementById('style').value,
            age: document.getElementById('age').value
        };
        localStorage.setItem('veo_preset', JSON.stringify(settings));
        showToast('Settings saved as preset!');
    });

    document.getElementById('loadPreset').addEventListener('click', () => {
        const saved = localStorage.getItem('veo_preset');
        if (saved) {
            const settings = JSON.parse(saved);
            Object.keys(settings).forEach(key => {
                const el = document.getElementById(key);
                if (el) el.value = settings[key];
            });
            showToast('Preset loaded!');
        } else {
            showToast('No preset found.');
        }
    });

    function showToast(msg) {
        toast.innerText = msg;
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 2000);
    }
});
