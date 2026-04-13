<script setup>
    import { ref }  from 'vue';
    

    const error = ref('');
    const success = ref(false);
    const formData = ref({
        name:'',
        email:'',
        password:'',
        password_confirmation:'',
        gender:'',
        birthday:''
    });
    
    const register = async (e) => {
        e.preventDefault();
        error.value = '';
        success.value = false;

        try {
            // Get CSRF token first
            await fetch('http://localhost:8000/sanctum/csrf-cookie', {
                credentials: 'include',
            });

            // Then make the register request
            const response = await fetch('http://localhost:8000/api/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(formData.value),
                credentials: 'include'
            });

            let data
            try {
                data = await response.json()
            } catch {
                error.value = 'Unexpected server response.'
                return
            }
            if (!response.ok) {
                if (data.errors) {
                    error.value = Object.values(data.errors).flat().join(', ');
                } else if (data.message) {
                    error.value = data.message;
                } else {
                    error.value = 'Registration failed. Please try again.';
                }
                console.error('Error:', data);
                return;
            }

            console.log('Success:', data);
            success.value = true;
        
            setTimeout(() => {
                window.location.href = '/';
            }, 1500);

        } catch (err) {
            error.value = 'Network error. Please try again.';
            console.error('Error:', err);
        }
    }
</script>
<template>
  <h1>register page</h1>
  <div v-if="error" style="color: red; padding: 10px; background-color: white;">
    {{ error }}
  </div>
  <div v-if="success" style="color: green; padding: 10px; background-color: white;">
    {{ success }}
  </div>
  <form @submit="register">
    <input v-model="formData.name" type="text" placeholder="enter your name..." required /><br />
    <input v-model="formData.email" type="email" placeholder="enter your email..." required /><br />
    <input v-model="formData.password" type="password" placeholder="enter your password..." required /><br />
    <input v-model="formData.password_confirmation" type="password" placeholder="confirm your password..." required /><br />
    
    <select v-model="formData.gender" required>
      <option value="">Select gender</option>
      <option value="male">Male</option>
      <option value="female">Female</option>
    </select><br />
    
    <input v-model="formData.birthday" type="date" placeholder="enter your birthday..." required /><br />
    
    <input type="submit" value="submit">
  </form>
</template>
