package com.philmoresms.app.ui.screens.profile

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.ErrorUtils
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.network.User
import com.philmoresms.app.ui.components.AppTopBar
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.components.LoadingBox
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class ProfileViewModel : ViewModel() {
    var user by mutableStateOf<User?>(null)
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    var username by mutableStateOf("")
    var email by mutableStateOf("")
    var phone by mutableStateOf("")

    var currentPassword by mutableStateOf("")
    var newPassword by mutableStateOf("")

    fun loadProfile() {
        loading = true
        error = null
        viewModelScope.launch {
            try {
                val u = RetrofitClient.apiService.getProfile().await().user
                user = u
                username = u?.username ?: ""
                email = u?.email ?: ""
                phone = u?.phone ?: ""
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to load profile"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                loading = false
            }
        }
    }

    fun saveProfile() {
        error = null
        success = null
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.updateProfile(username, email, phone).await()
                success = res.message ?: "Profile updated"
                user = user?.copy(username = username, email = email, phone = phone)
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to update profile"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            }
        }
    }

    fun changePassword() {
        error = null
        success = null
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.changePassword(currentPassword, newPassword).await()
                success = res.message ?: "Password changed"
                currentPassword = ""
                newPassword = ""
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to change password"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            }
        }
    }
}

@Composable
fun ProfileScreen(navController: NavController) {
    val viewModel: ProfileViewModel = viewModel()
    LaunchedEffect(Unit) { viewModel.loadProfile() }

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("My Profile", onBack = { navController.popBackStack() })

        if (viewModel.loading) {
            LoadingBox()
        } else {
            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(20.dp)
            ) {
                Surface(
                    modifier = Modifier.size(80.dp).align(Alignment.CenterHorizontally),
                    shape = CircleShape,
                    color = Primary
                ) {
                    Box(contentAlignment = Alignment.Center) { Icon(Icons.Default.Person, contentDescription = null, tint = Color.White, modifier = Modifier.size(40.dp)) }
                }

                Text(
                    "Balance: ₦${viewModel.user?.balance ?: 0.0}",
                    fontSize = 18.sp,
                    fontWeight = FontWeight.Bold,
                    color = Primary,
                    modifier = Modifier.align(Alignment.CenterHorizontally).padding(top = 12.dp)
                )
                Text(
                    "Referral code: ${viewModel.user?.referralCode ?: "-"}",
                    fontSize = 13.sp,
                    color = TextSecondary,
                    modifier = Modifier.align(Alignment.CenterHorizontally).padding(top = 4.dp, bottom = 24.dp)
                )

                if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))
                if (viewModel.success != null) Text(viewModel.success ?: "", color = Success, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))

                Text("Account Details", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = TextPrimary, modifier = Modifier.padding(bottom = 16.dp))

                FintechInput("Username", viewModel.username, { viewModel.username = it })
                FintechInput("Email", viewModel.email, { viewModel.email = it })
                FintechInput("Phone Number", viewModel.phone, { viewModel.phone = it })
                FintechButton("Save Changes", { viewModel.saveProfile() })

                Divider(modifier = Modifier.padding(vertical = 24.dp))

                Text("Change Password", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = TextPrimary, modifier = Modifier.padding(bottom = 16.dp))
                FintechInput("Current Password", viewModel.currentPassword, { viewModel.currentPassword = it }, secureTextEntry = true)
                FintechInput("New Password", viewModel.newPassword, { viewModel.newPassword = it }, secureTextEntry = true)
                FintechButton("Change Password", { viewModel.changePassword() })

                Spacer(Modifier.height(24.dp))
            }
        }
    }
}
