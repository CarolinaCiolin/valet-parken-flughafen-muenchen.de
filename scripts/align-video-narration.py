"""Align approved captions with recognized speech; keep public wording unchanged."""
from pathlib import Path
import difflib,json
R=Path(__file__).resolve().parents[1];P=R/'media/valet-erklaervideo/projekt'
report=json.loads((P/'sprechpruefung.json').read_text());segments=json.loads((P/'sprechsegmente.json').read_text());words=report['recognized_words']
def norm(text):
    text=text.casefold().replace('ä','ae').replace('ö','oe').replace('ü','ue').replace('ß','ss')
    return ''.join(x for x in text if x.isalnum())
reference=''.join(norm(x) for x in segments);actual='';times=[]
for w in words:
    n=norm(w['text']);actual+=n
    for i in range(len(n)):times.append((w['start']+(w['end']-w['start'])*i/len(n),w['start']+(w['end']-w['start'])*(i+1)/len(n)))
matcher=difflib.SequenceMatcher(None,reference,actual,autojunk=False);mapping={}
for block in matcher.get_matching_blocks():
    for i in range(block.size):mapping[block.a+i]=block.b+i
coverage=len(mapping)/len(reference)
assert coverage>=.90,('Speech differs significantly from approved script; inspect recognition',coverage,actual)
at=0;alignment=[]
for i,text in enumerate(segments):
    size=len(norm(text));hits=[k for k in range(at,at+size) if k in mapping];assert hits
    start=times[mapping[hits[0]]][0];end=times[mapping[hits[-1]]][1]
    alignment.append({'index':i,'start':round(start,4),'end':round(end,4),'text':text});at+=size
assert all(a['start']<a['end'] for a in alignment)
assert alignment[-1]['end']<=report['duration']+.1
result={'sample_rate':report['sample_rate'],'duration':report['duration'],'voice':report['model'],'source':'https://huggingface.co/'+report['model'],'alignment_method':'Whisper word timing plus approved-text character alignment','recognized_character_coverage':round(coverage,5),'segments':alignment}
(P/'sprachzeiten.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n')
design=json.loads((P/'design.json').read_text());design.update(duration=53.5,voice_lufs=-16,music_lufs=-26,ending_fade_seconds=1.3)
design['chapters']=[0,4.2,alignment[2]['start'],alignment[3]['start'],alignment[5]['start'],alignment[7]['start']]
assert sorted(design['chapters'])==design['chapters'];assert design['duration']-design['chapters'][-1]>=6
(P/'design.json').write_text(json.dumps(design,ensure_ascii=False,indent=2)+'\n')
print('Aligned approved captions:',round(coverage*100,2),'% recognized character coverage; closing',round(design['duration']-design['chapters'][-1],2),'seconds')
